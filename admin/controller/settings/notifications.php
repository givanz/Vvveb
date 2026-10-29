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

namespace Vvveb\Controller\Settings;

use function Vvveb\__;
use function Vvveb\config;
use Vvveb\Controller\Base;
use function Vvveb\setConfig;

class Notifications extends Base {
	/**
	 * Display notification settings (events table + provider config tabs).
	 *
	 * @return void
	 */
	public function index(): void {
		// Ensure all default events are registered
		\Vvveb\System\Notifications::registerDefaults();

		$config = config('notifications', []);
		$events = $config['events'] ?? [];

		// Build a list of all available provider slugs for the table columns
		$providerClasses = \Vvveb\System\Notifications::getProviderClasses();
		$providerSlugs   = array_keys($providerClasses);

		// Build named webhooks list for extra columns
		$namedWebhooks  = $config['providers']['webhooks']['webhooks'] ?? [];
		$webhookNames   = array_map(fn ($wh) => $wh['name'] ?? '', $namedWebhooks);
		$webhookNames   = array_filter($webhookNames);

		// Get events grouped by category
		$groupedEvents = \Vvveb\System\Notifications::getEventsGrouped();

		// Provider settings fields for the config tabs
		$providerFields = [];

		foreach ($providerClasses as $slug => $class) {
			$providerFields[$slug] = $class::settingsFields();
		}

		$this->view->events            = $events;
		$this->view->providerSlugs     = $providerSlugs;
		$this->view->webhookNames      = $webhookNames;
		$this->view->groupedEvents     = $groupedEvents;
		$this->view->providerFields    = $providerFields;
		$this->view->config            = $config;
		$this->view->providers         = $config['providers'] ?? [];
		$this->view->lowStockThreshold = $config['low_stock_threshold'] ?? 5;

		// Category labels for display
		$this->view->categories = [
			'content'    => __('Content'),
			'users'      => __('Users'),
			'commerce'   => __('Commerce'),
			'engagement' => __('Engagement'),
			'media'      => __('Media'),
			'system'     => __('System'),
			'other'      => __('Other'),
		];
	}

	/**
	 * Save notification settings.
	 *
	 * @return void
	 */
	public function save(): void {
		if ($this->checkCsrf() === false) {
			return;
		}

		$settings = $this->request->post['settings'] ?? null;

		if ($settings && is_array($settings)) {
			// Sanitize: ensure events is an array
			if (isset($settings['events']) && is_array($settings['events'])) {
				foreach ($settings['events'] as &$eventConfig) {
					$eventConfig['providers']           = array_values((array)($eventConfig['providers'] ?? []));
					$eventConfig['recipients']          = array_values((array)($eventConfig['recipients'] ?? ['site_admin']));
					$eventConfig['additional_emails']   = array_filter(array_map('trim', (array)($eventConfig['additional_emails'] ?? [])));
					$eventConfig['additional_phones']   = array_filter(array_map('trim', (array)($eventConfig['additional_phones'] ?? [])));
					$eventConfig['additional_webhooks'] ??= [];
				}
				unset($eventConfig);
			}

			// Sanitize: ensure providers has required structure
			if (isset($settings['providers']) && is_array($settings['providers'])) {
				foreach ($settings['providers'] as $slug => &$providerConfig) {
					if (!is_array($providerConfig)) {
						$providerConfig = ['enabled' => false];
					}
					$providerConfig['enabled'] = !empty($providerConfig['enabled']);
				}
				unset($providerConfig);

				// Sanitize named webhook endpoints
				$definedWebhooks = $settings['providers']['webhooks']['webhooks'] ?? [];

				if (is_array($definedWebhooks)) {
					$list = [];

					foreach ($definedWebhooks as $webhook) {
						if (!is_array($webhook) || empty($webhook['url'])) {
							continue;
						}

						$method = strtoupper((string)($webhook['method'] ?? 'POST'));

						$list[] = [
							'name'    => trim((string)($webhook['name'] ?? '')) ?: ('Webhook ' . (count($list) + 1)),
							'url'     => trim((string)$webhook['url']),
							'method'  => in_array($method, ['POST', 'PUT', 'PATCH', 'GET'], true) ? $method : 'POST',
							'headers' => is_string($webhook['headers'] ?? null) ? trim($webhook['headers']) : '',
							'enabled' => !empty($webhook['enabled']),
						];
					}

					$settings['providers']['webhooks']['webhooks'] = $list;
				}

				// Global cooldown
				if (isset($settings['providers']['global']['cooldown'])) {
					$settings['providers']['global']['cooldown'] = max(0, (int)$settings['providers']['global']['cooldown']);
				}
			}

			// Per-event cooldowns and webhook selections
			foreach (($settings['events'] ?? []) as &$eventConfig2) {
				if (isset($eventConfig2['additional_webhooks']) && is_array($eventConfig2['additional_webhooks'])) {
					$eventConfig2['additional_webhooks'] = array_values(array_filter(array_map('trim', $eventConfig2['additional_webhooks'])));
				}

				if (isset($eventConfig2['cooldown']) && $eventConfig2['cooldown'] !== '') {
					$eventConfig2['cooldown'] = max(0, (int)$eventConfig2['cooldown']);
				} else {
					unset($eventConfig2['cooldown']);
				}
			}
			unset($eventConfig2);

			// Handle low stock threshold
			$settings['low_stock_threshold'] = max(0, (int)($settings['low_stock_threshold'] ?? 5));

			$results = setConfig('notifications', $settings);

			if ($results) {
				$this->view->success[] = __('Settings saved!');
			} else {
				$this->view->errors[] = __('Error saving!');
			}
		} else {
			$this->view->errors[] = __('Invalid settings data!');
		}

		$this->index();
	}
}
