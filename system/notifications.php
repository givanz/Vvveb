<?php

/**
 * Vvveb
 *
 * Copyright (C) 2022  Ziadin Givan
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 */

namespace Vvveb\System;

/**
 * Notification system — registers events, triggers them, and routes to providers.
 *
 * Mirrors the Event class API pattern so core code and plugins have a familiar interface.
 */
class Notifications {
	/** @var array<string, array{description: string, category: string, default_recipients?: string[]}> Registered notification events */
	private static array $events = [];

	/** @var array<string, class-string<Notifications\Providers\Provider>> Available provider classes (keyed by slug) */
	private static array $providerClasses = [];

	/** @var array<string, array{enabled: bool, ...}> Loaded provider settings from config */
	private static array $providerSettings = [];

	/** @var array<string, array{providers: string[], recipients: string[], additional_emails: string[], additional_phones: string[], additional_webhooks: string[], cooldown?: int|string}> Event routing from config */
	private static array $eventSettings = [];

	/** @var bool Whether providers have been bootstrapped */
	private static bool $booted = false;

	/** @var array<string, int> Rate-limit last-sent timestamps (event.recipient.provider => timestamp) */
	private static array $rateLimits = [];

	// ── Registration ───────────────────────────────────────────────────

	/**
	 * Register a notification event so it appears in admin settings and can receive providers.
	 *
	 * @param string                  $event  Dot-separated event name (e.g. 'order.new')
	 * @param array<array-key, mixed> $config { description, category, default_recipients }
	 */
	public static function register(string $event, array $config = []): void {
		if (isset(self::$events[$event])) {
			return; // idempotent
		}

		self::$events[$event] = [
			'description'        => $config['description'] ?? $event,
			'category'           => $config['category'] ?? 'other',
			'default_recipients' => $config['default_recipients'] ?? ['site_admin'],
		];
	}

	/**
	 * Check a product against the global low-stock threshold and trigger
	 * product.lowstock if quantity dropped to or below it.
	 *
	 * @param array<array-key, mixed> $product Product record with at least product_id + quantity
	 */
	public static function checkLowStock(array $product): void {
		$threshold = \Vvveb\config('notifications.low_stock_threshold', null);

		// No threshold configured (or disabled with 0? 0 means alert when out of stock)
		if ($threshold === null || $threshold === '') {
			return;
		}

		$quantity = (int)($product['quantity'] ?? 0);

		if ($quantity > (int)$threshold) {
			return;
		}

		$name   = $product['name'] ?? ('#' . ($product['product_id'] ?? '?'));
		$dedup  = 'product.' . ($product['product_id'] ?? ($product['sku'] ?? md5(json_encode($product))));

		self::trigger('product.lowstock', $product + [
			'_dedup_key' => $dedup,
			'subject'    => sprintf('Low stock: %s', $name),
			'message'    => sprintf('%s stock fell to %d (threshold %d).', $name, $quantity, (int)$threshold),
		]);
	}

	/**
	 * Return all registered events, optionally filtered by category.
	 *
	 * @param string|false $category
	 *
	 * @return array<string, array{description: string, category: string, default_recipients?: string[]}>
	 */
	public static function getEvents(string|false $category = false): array {
		if ($category !== false) {
			return array_filter(self::$events, fn ($e) => $e['category'] === $category);
		}

		return self::$events;
	}

	// ── Trigger ────────────────────────────────────────────────────────

	/**
	 * Trigger a notification event, dispatching to all enabled providers for that event.
	 *
	 * @param string                  $event Event name
	 * @param array<array-key, mixed> $data  Event-specific payload
	 */
	public static function trigger(string $event, array $data = []): void {
		// Always let plugins/core hook into the trigger
		Event::trigger(self::class, 'trigger:before', $event, $data);

		// Ensure built-in events are registered + config loaded before dispatching.
		// Idempotent and cheap after the first call; makes trigger() safe as the
		// very first entry point without a prior explicit registration.
		self::boot();

		if (!isset(self::$events[$event])) {
			return; // unregistered event — silently skip
		}

		$eventConfig = self::$eventSettings[$event] ?? null;

		if (!$eventConfig) {
			return; // no routing configured for this event
		}

		$enabledProviders = $eventConfig['providers'];

		if (empty($enabledProviders)) {
			return;
		}

		// Build recipients list
		$recipients    = self::resolveRecipients($event, $eventConfig, $data);
		// Optional extra dedup discriminator (e.g. "product.42") so identical events
		// for different subjects don't suppress each other
		$dedupKey      = isset($data['_dedup_key']) ? '.' . $data['_dedup_key'] : '';

		// Recipient-less routing (e.g. webhook-only events): dispatch once
		if (!$recipients) {
			$recipients[] = ['id' => 'none'];
		}

		foreach ($recipients as $recipient) {
			$recipientEmail = $recipient['email'] ?? '';
			$recipientPhone = $recipient['phone'] ?? '';
			$recipientName  = $recipient['name'] ?? '';
			$recipientId    = ($recipient['id'] ?? $recipientEmail) . $dedupKey;

			// Build envelope (internal routing keys are not exposed to providers)
			$envelopeData = $data;
			unset($envelopeData['_dedup_key']);

			$envelope = [
				'event'     => $event,
				'timestamp' => time(),
				'recipient' => $recipient,
				'subject'   => $data['subject'] ?? self::$events[$event]['description'],
				'message'   => $data['message'] ?? '',
				'data'      => $envelopeData,
			];

			foreach ($enabledProviders as $providerSlug) {
				// Skip disabled providers
				if (empty(self::$providerSettings[$providerSlug]['enabled'] ?? false)) {
					continue;
				}

				// Rate-limit check
				if (self::isRateLimited($event, $recipientId, $providerSlug)) {
					continue;
				}

				$providerClass = self::$providerClasses[$providerSlug] ?? null;

				if (!$providerClass || !class_exists($providerClass)) {
					continue;
				}

				try {
					$providerSettings = array_key_exists($providerSlug, self::$providerSettings) ? self::$providerSettings[$providerSlug] : [];

					// Per-event named webhook selection: pass which of the globally defined
					// webhooks (plus any free-form URLs) apply to this event
					if ($providerSlug === 'webhooks') {
						$providerSettings['_selected'] = $eventConfig['additional_webhooks'];
					}

					$result = $providerClass::send($event, $envelope, $providerSettings);

					if ($result) {
						self::recordRateLimit($event, $recipientId, $providerSlug);
					}
				} catch (\Throwable $e) {
					// Log but never let one provider block others
					if (function_exists('error_log')) {
						error_log("[Vvveb Notifications] Provider '$providerSlug' failed for event '$event': " . $e->getMessage());
					}
				}
			}
		}

		Event::trigger(self::class, 'trigger:after', $event, $data);
	}

	// ── Recipient resolution ───────────────────────────────────────────

	/**
	 * Resolve all recipients for an event from config + data.
	 *
	 * @param string                  $event
	 * @param array<array-key, mixed> $eventConfig
	 * @param array<array-key, mixed> $data
	 *
	 * @return list<array{id?: string, name?: string, email?: string, phone?: string}>
	 */
	private static function resolveRecipients(string $event, array $eventConfig, array $data): array {
		$recipients       = [];

		// Explicit "to" override replaces auto-resolved targets (additional_* still append)
		if (!empty($data['to'])) {
			$eventConfig['recipients'] = [];

			foreach ((array)$data['to'] as $toEmail) {
				$recipients[] = ['id' => 'to:' . $toEmail, 'email' => $toEmail];
			}
		}

		$recipientTargets = $eventConfig['recipients'] ?? ['site_admin'];
		$site             = \Vvveb\siteSettings();
		$siteEmail        = $site['contact-email'] ?? $site['admin-email'] ?? '';
		$siteName         = is_array($site['description'] ?? null)
			? ($site['description']['title'] ?? '')
			: ($site['description'] ?? '');

		foreach ($recipientTargets as $target) {
			switch ($target) {
				case 'site_admin':
					$recipients[] = [
						'id'    => 'site_admin',
						'name'  => $siteName,
						'email' => $siteEmail,
					];
					break;

				case 'user':
					// The subject of the event (customer/user)
					$source    = self::payloadSubject($data);
					$userEmail = $source['email'] ?? '';
					$userName  = trim(($source['first_name'] ?? '') . ' ' . ($source['last_name'] ?? ''));
					$userName  = $userName ?: ($source['username'] ?? '');
					$userPhone = $source['phone'] ?? $source['telephone'] ?? '';

					if ($userEmail) {
						$recipients[] = [
							'id'    => 'user:' . $userEmail,
							'name'  => $userName,
							'email' => $userEmail,
							'phone' => $userPhone,
						];
					}
					break;

				case 'admin_user':
					// The specific admin/staff user tied to the record
					$adminEmail = $data['admin_email'] ?? $data['author_email'] ?? '';
					$adminName  = $data['admin_name'] ?? $data['author_name'] ?? '';

					if (!$adminEmail && ($admin = self::payloadSubject($data, 'admin')) && !empty($admin['email'])) {
						$adminEmail = $admin['email'];
						$adminName  = $adminName ?: trim(($admin['first_name'] ?? '') . ' ' . ($admin['last_name'] ?? ''));
					}

					if ($adminEmail) {
						$recipients[] = [
							'id'    => 'admin_user:' . $adminEmail,
							'name'  => $adminName,
							'email' => $adminEmail,
						];
					}
					break;
			}
		}

		// Additional emails
		foreach ($eventConfig['additional_emails'] ?? [] as $email) {
			$email = trim($email);

			if ($email) {
				$recipients[] = [
					'id'    => 'extra_email:' . $email,
					'name'  => '',
					'email' => $email,
				];
			}
		}

		// Additional phones
		foreach ($eventConfig['additional_phones'] ?? [] as $phone) {
			$phone = trim($phone);

			if ($phone) {
				$recipients[] = [
					'id'    => 'extra_phone:' . $phone,
					'name'  => '',
					'phone' => $phone,
				];
			}
		}

		return $recipients;
	}

	/**
	 * Extract the subject record (user/customer/order...) from a payload.
	 * Looks into common nested keys first, falls back to top-level keys.
	 *
	 * @param array<array-key, mixed> $data Event payload
	 * @param string                  $kind Preferred nested key ('user', 'customer', 'admin', ...)
	 *
	 * @return array<array-key, mixed>
	 */
	private static function payloadSubject(array $data, string $kind = 'user'): array {
		foreach ([$kind, 'customer', 'user', 'order', 'post', 'admin'] as $key) {
			if (isset($data[$key]) && is_array($data[$key])) {
				return $data[$key];
			}
		}

		return $data;
	}

	// ── Provider bootstrap ─────────────────────────────────────────────

	/**
	 * Load available providers and settings from config. Called once per request on first trigger.
	 */
	private static function boot(): void {
		if (self::$booted) {
			return;
		}

		self::$booted = true;

		// Make sure all built-in events are registered even if trigger() is the
		// first entry point (registration is idempotent)
		self::registerDefaults();

		// Load provider settings from config
		$config = \Vvveb\config('notifications', []);

		self::$providerSettings = $config['providers'] ?? [];
		self::$eventSettings    = $config['events'] ?? [];

		// Load built-in providers
		self::$providerClasses = [
			'email'    => Notifications\Providers\Email::class,
			'sms'      => Notifications\Providers\Sms::class,
			'slack'    => Notifications\Providers\Slack::class,
			'whatsapp' => Notifications\Providers\Whatsapp::class,
			'telegram' => Notifications\Providers\Telegram::class,
			'webhooks' => Notifications\Providers\Webhooks::class,
		];

		// Let plugins register additional providers (same pattern as payment gateways)
		$availableProviders   = [];
		[$availableProviders] = Event::trigger('Vvveb\Notifications\Providers', 'register', $availableProviders);

		foreach ($availableProviders as $slug => $class) {
			if (!isset(self::$providerClasses[$slug])) {
				self::$providerClasses[$slug] = $class;
			}
		}
	}

	/**
	 * Get all available provider classes (built-in + plugin-registered).
	 *
	 * @return array<string, class-string<Notifications\Providers\Provider>>
	 */
	public static function getProviderClasses(): array {
		self::boot();

		return self::$providerClasses;
	}

	/**
	 * Get provider settings from config.
	 *
	 * @return array<string, array{enabled: bool, ...}>
	 */
	public static function getProviderSettings(): array {
		self::boot();

		return self::$providerSettings;
	}

	/**
	 * Get event routing settings from config.
	 *
	 * @return array<string, array{providers: string[], recipients: string[], ...}>
	 */
	public static function getEventSettings(): array {
		self::boot();

		return self::$eventSettings;
	}

	// ── Rate limiting ──────────────────────────────────────────────────

	/**
	 * Check whether an event should be suppressed due to rate limiting.
	 *
	 * @param string $event
	 * @param string $recipientId
	 * @param string $provider
	 */
	private static function isRateLimited(string $event, string $recipientId, string $provider): bool {
		$key = "$event.$recipientId.$provider";

		if (!isset(self::$rateLimits[$key])) {
			// Load from cache/store
			$lastSent               = self::loadRateLimit($event, $recipientId, $provider);
			self::$rateLimits[$key] = $lastSent;
		}

		$cooldown = self::getCooldown($event, $provider);

		if ($cooldown > 0 && self::$rateLimits[$key] > 0) {
			$elapsed = time() - self::$rateLimits[$key];

			if ($elapsed < $cooldown) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Record that a notification was sent for rate-limiting purposes.
	 *
	 * @param string $event
	 * @param string $recipientId
	 * @param string $provider
	 */
	private static function recordRateLimit(string $event, string $recipientId, string $provider): void {
		$key                    = "$event.$recipientId.$provider";
		$now                    = time();
		self::$rateLimits[$key] = $now;

		// Persist to cache
		$cacheKey = "notification_ratelimit.$event.$recipientId.$provider";

		try {
			$cache = Cache::getInstance();
			$cache->set($cacheKey, 'notification', $now, 86400); // 24h TTL
		} catch (\Throwable $e) {
			// Best-effort — in-memory is fine as fallback
		}
	}

	/**
	 * Load last-sent timestamp from cache.
	 *
	 * @param string $event
	 * @param string $recipientId
	 * @param string $provider
	 */
	private static function loadRateLimit(string $event, string $recipientId, string $provider): int {
		try {
			$cacheKey = "notification_ratelimit.$event.$recipientId.$provider";
			$cache    = Cache::getInstance();
			$lastSent = $cache->get($cacheKey, 'notification');

			return is_int($lastSent) ? $lastSent : 0;
		} catch (\Throwable $e) {
			return 0;
		}
	}

	/**
	 * Get cooldown window in seconds for a provider.
	 *
	 * @param string $event
	 * @param string $provider
	 */
	private static function getCooldown(string $event, string $provider): int {
		// Per-event cooldown wins over everything else
		$eventCooldown = self::$eventSettings[$event]['cooldown'] ?? null;

		if ($eventCooldown !== null && $eventCooldown !== '') {
			return max(0, (int)$eventCooldown);
		}

		// Per-provider cooldown from config
		$providerCooldown = self::$providerSettings[$provider]['cooldown'] ?? null;

		if ($providerCooldown !== null) {
			return (int)$providerCooldown;
		}

		// Global cooldown
		$globalCooldown = self::$providerSettings['global']['cooldown'] ?? null;

		if ($globalCooldown !== null) {
			return (int)$globalCooldown;
		}

		// Defaults: email=no cooldown, SMS/Slack/WhatsApp/webhooks=1 hour
		$defaults = [
			'email'    => 0,
			'sms'      => 3600,
			'slack'    => 3600,
			'whatsapp' => 3600,
			'telegram' => 3600,
			'webhooks' => 0,
		];

		return $defaults[$provider] ?? 3600;
	}

	// ── Registration of all built-in events ────────────────────────────

	/**
	 * Register all built-in notification events. Call once during bootstrap.
	 */
	public static function registerDefaults(): void {
		// ── Content — Posts ──
		self::register('post.added', ['description' => 'A post is created',                'category' => 'content']);
		self::register('post.edited', ['description' => 'A post is edited',                  'category' => 'content']);
		self::register('post.deleted', ['description' => 'A post is deleted',                 'category' => 'content']);
		self::register('post.published', ['description' => 'A post is published',               'category' => 'content', 'default_recipients' => ['admin_user']]);
		self::register('post.unpublished', ['description' => 'A post is unpublished',             'category' => 'content', 'default_recipients' => ['admin_user']]);
		self::register('post.scheduled', ['description' => 'A post is scheduled',               'category' => 'content', 'default_recipients' => ['admin_user']]);
		self::register('post.unscheduled', ['description' => 'A post schedule is cancelled',      'category' => 'content', 'default_recipients' => ['admin_user']]);
		self::register('post.rescheduled', ['description' => 'A post schedule time is changed',   'category' => 'content', 'default_recipients' => ['admin_user']]);
		self::register('post.tag.attached', ['description' => 'A tag is attached to a post',       'category' => 'content']);
		self::register('post.tag.detached', ['description' => 'A tag is detached from a post',     'category' => 'content']);

		// ── Content — Pages ──
		self::register('page.added', ['description' => 'A page is created',                 'category' => 'content']);
		self::register('page.edited', ['description' => 'A page is edited',                  'category' => 'content']);
		self::register('page.deleted', ['description' => 'A page is deleted',                 'category' => 'content']);
		self::register('page.published', ['description' => 'A page is published',               'category' => 'content', 'default_recipients' => ['admin_user']]);
		self::register('page.published.edited', ['description' => 'An already-published page is edited', 'category' => 'content', 'default_recipients' => ['admin_user']]);
		self::register('page.unpublished', ['description' => 'A page is unpublished',             'category' => 'content', 'default_recipients' => ['admin_user']]);
		self::register('page.scheduled', ['description' => 'A page is scheduled',               'category' => 'content', 'default_recipients' => ['admin_user']]);
		self::register('page.unscheduled', ['description' => 'A page schedule is cancelled',      'category' => 'content', 'default_recipients' => ['admin_user']]);
		self::register('page.rescheduled', ['description' => 'A page schedule time is changed',   'category' => 'content', 'default_recipients' => ['admin_user']]);
		self::register('page.tag.attached', ['description' => 'A tag is attached to a page',       'category' => 'content']);
		self::register('page.tag.detached', ['description' => 'A tag is detached from a page',     'category' => 'content']);

		// ── Content — Tags & site ──
		self::register('tag.added', ['description' => 'A tag is created',   'category' => 'content']);
		self::register('tag.edited', ['description' => 'A tag is edited',    'category' => 'content']);
		self::register('tag.deleted', ['description' => 'A tag is deleted',   'category' => 'content']);
		self::register('site.changed', ['description' => 'Site content or setting changes', 'category' => 'content']);

		// ── Users ──
		self::register('user.signup', ['description' => 'A new user self-registers',  'category' => 'users', 'default_recipients' => ['user', 'site_admin']]);
		self::register('user.added', ['description' => 'A user is created by admin', 'category' => 'users', 'default_recipients' => ['site_admin']]);
		self::register('user.edited', ['description' => 'A user account is edited',   'category' => 'users', 'default_recipients' => ['site_admin']]);
		self::register('user.deleted', ['description' => 'A user is deleted',          'category' => 'users', 'default_recipients' => ['site_admin']]);

		// Migrated from legacy direct mail() password-reset flows
		self::register('user.password_reset', ['description' => 'A storefront user requests a password reset', 'category' => 'users']);
		self::register('admin.password_reset', ['description' => 'An admin user requests a password reset',     'category' => 'system']);

		// ── Commerce — Products ──
		self::register('product.added', ['description' => 'A product is created',                  'category' => 'commerce']);
		self::register('product.edited', ['description' => 'A product is edited',                    'category' => 'commerce']);
		self::register('product.deleted', ['description' => 'A product is deleted',                   'category' => 'commerce']);
		self::register('product.lowstock', ['description' => 'A product stock falls below threshold',  'category' => 'commerce']);

		// ── Commerce — Orders ──
		self::register('order.new', ['description' => 'A customer places a new order',          'category' => 'commerce', 'default_recipients' => ['user', 'site_admin']]);
		self::register('order.added', ['description' => 'An order is created by admin',           'category' => 'commerce', 'default_recipients' => ['site_admin']]);
		self::register('order.deleted', ['description' => 'An order is deleted',                    'category' => 'commerce', 'default_recipients' => ['site_admin']]);
		self::register('order.status_change', ['description' => 'An order status changes',                'category' => 'commerce', 'default_recipients' => ['user', 'site_admin']]);

		// ── Engagement — Comments, reviews & questions ──
		self::register('comment.added', ['description' => 'A new comment is posted',                    'category' => 'engagement', 'default_recipients' => ['admin_user']]);
		self::register('comment.edited', ['description' => 'A comment is edited',                        'category' => 'engagement', 'default_recipients' => ['admin_user']]);
		self::register('comment.deleted', ['description' => 'A comment is deleted',                       'category' => 'engagement', 'default_recipients' => ['admin_user']]);
		self::register('product.review.added', ['description' => 'A new product review is submitted',          'category' => 'engagement', 'default_recipients' => ['admin_user']]);
		self::register('product.review.deleted', ['description' => 'A product review is deleted',                'category' => 'engagement', 'default_recipients' => ['admin_user']]);
		self::register('product.question.added', ['description' => 'A new product question is submitted',        'category' => 'engagement', 'default_recipients' => ['admin_user']]);
		self::register('product.question.answered', ['description' => 'A product question receives an answer',      'category' => 'engagement', 'default_recipients' => ['admin_user']]);

		// ── Commerce — Subscriptions & payments ──
		self::register('subscription.add', ['description' => 'A customer starts a subscription',     'category' => 'commerce', 'default_recipients' => ['user', 'site_admin']]);
		self::register('subscription.cancel', ['description' => 'A subscription is cancelled',          'category' => 'commerce', 'default_recipients' => ['user', 'site_admin']]);
		self::register('payment.failed', ['description' => 'A payment attempt fails',              'category' => 'commerce', 'default_recipients' => ['user', 'site_admin']]);
		self::register('payment.refunded', ['description' => 'A payment is refunded',                'category' => 'commerce', 'default_recipients' => ['user', 'site_admin']]);
		self::register('order.return', ['description' => 'A customer initiates a return',        'category' => 'commerce', 'default_recipients' => ['user', 'site_admin']]);

		// ── Media ──
		self::register('media.new', ['description' => 'A new media file is uploaded', 'category' => 'media', 'default_recipients' => ['site_admin']]);
		self::register('media.delete', ['description' => 'A media file is deleted',      'category' => 'media', 'default_recipients' => ['site_admin']]);

		// ── Plugins & themes ──
		self::register('plugin.add', ['description' => 'A plugin is installed',      'category' => 'system', 'default_recipients' => ['site_admin']]);
		self::register('plugin.delete', ['description' => 'A plugin is uninstalled',    'category' => 'system', 'default_recipients' => ['site_admin']]);
		self::register('plugin.activate', ['description' => 'A plugin is activated',      'category' => 'system', 'default_recipients' => ['site_admin']]);
		self::register('plugin.deactivate', ['description' => 'A plugin is deactivated',    'category' => 'system', 'default_recipients' => ['site_admin']]);
		self::register('theme.add', ['description' => 'A theme is installed',        'category' => 'system', 'default_recipients' => ['site_admin']]);
		self::register('theme.delete', ['description' => 'A theme is deleted',          'category' => 'system', 'default_recipients' => ['site_admin']]);
		self::register('theme.activate', ['description' => 'A theme is activated',        'category' => 'system', 'default_recipients' => ['site_admin']]);

		// ── Other ──
		self::register('contact-form.message', ['description' => 'A contact form message is received', 'category' => 'other']);
		self::register('cronjob.fail', ['description' => 'A scheduled cron job fails',         'category' => 'system', 'default_recipients' => ['site_admin']]);
		self::register('php.fatal_error', ['description' => 'A PHP fatal error occurs',           'category' => 'system', 'default_recipients' => ['site_admin']]);

		// ── System — Queue ──
		self::register('queue.job.failed', ['description' => 'A background queue job fails permanently', 'category' => 'system', 'default_recipients' => ['site_admin']]);

		// Let plugins register their own notification events
		Event::trigger(self::class, 'register');
	}

	// ── Admin helpers ──────────────────────────────────────────────────

	/**
	 * Get events grouped by category for admin display.
	 *
	 * @return array<string, list<array{event: string, description: string, category: string, default_recipients: string[]}>>
	 */
	public static function getEventsGrouped(): array {
		$grouped = [];

		foreach (self::$events as $event => $config) {
			$category = $config['category'];

			if (!isset($grouped[$category])) {
				$grouped[$category] = [];
			}

			$grouped[$category][] = [
				'event'              => $event,
				'description'        => $config['description'],
				'category'           => $category,
				'default_recipients' => $config['default_recipients'] ?? ['site_admin'],
			];
		}

		return $grouped;
	}
}
