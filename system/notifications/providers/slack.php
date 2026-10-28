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

namespace Vvveb\System\Notifications\Providers;

/**
 * Slack notification provider — sends via Incoming Webhook URL.
 */
class Slack implements Provider {
	/**
	 * Send a Slack notification.
	 *
	 * @param string                  $event    The notification event name
	 * @param array<array-key, mixed> $data     The full notification envelope
	 * @param array<array-key, mixed> $settings Provider settings (webhook_url, channel)
	 *
	 * @return bool
	 */
	public static function send(string $event, array $data, array $settings): bool {
		$webhookUrl = $settings['webhook_url'] ?? '';

		if (!$webhookUrl) {
			return false;
		}

		$recipient = $data['recipient'] ?? [];
		$subject   = $data['subject'] ?? $event;
		$message   = $data['message'] ?? '';
		$siteName  = $data['data']['site_name'] ?? \Vvveb\config('site.name', 'Vvveb');

		// Build Slack message payload.
		// Header blocks are capped at 150 chars and section text at 3000 by the
		// Slack API — longer values get the whole request rejected with 400
		$subject = mb_substr($subject, 0, 150);

		$blocks = [
			[
				'type' => 'header',
				'text' => [
					'type'  => 'plain_text',
					'text'  => $subject,
					'emoji' => true,
				],
			],
		];

		if ($message) {
			$blocks[] = [
				'type' => 'section',
				'text' => [
					'type' => 'mrkdwn',
					'text' => mb_substr($message, 0, 3000),
				],
			];
		}

		// Add recipient info if available
		$recipientName = $recipient['name'] ?? $recipient['email'] ?? '';

		if ($recipientName) {
			$blocks[] = [
				'type'     => 'context',
				'elements' => [
					[
						'type' => 'mrkdwn',
						'text' => "👤 Recipient: $recipientName",
					],
				],
			];
		}

		// Per-event channel override (e.g. route different events to different channels)
		$channel = $recipient['channel'] ?? ($settings['channel'] ?? '');

		$payload = [
			'username'   => $siteName . ' Notifications',
			'icon_emoji' => ':bell:',
			'blocks'     => $blocks,
		];

		// Empty channel falls back to the webhook's configured default
		if (!empty($channel)) {
			$payload['channel'] = $channel;
		}

		$json = json_encode($payload);

		$ch = curl_init($webhookUrl);

		curl_setopt_array($ch, [
			CURLOPT_POST           => true,
			CURLOPT_POSTFIELDS     => $json,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_TIMEOUT        => 10,
			CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
		]);

		$response = curl_exec($ch);
		$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		curl_close($ch);

		if ($httpCode !== 200 || !is_string($response)) {
			return false;
		}

		$response = trim($response);

		// Incoming webhooks answer plain "ok" on success; some responses are JSON {"ok":true}
		if ($response === 'ok') {
			return true;
		}

		$body = json_decode($response, true);

		return is_array($body) && !empty($body['ok']);
	}

	/**
	 * Settings fields for admin.
	 */
	public static function settingsFields(): array {
		return [
			[
				'type'        => 'toggle',
				'name'        => 'enabled',
				'label'       => 'Enable Slack Notifications',
				'description' => 'Send notifications to Slack via Incoming Webhook',
			],
			[
				'type'        => 'url',
				'name'        => 'webhook_url',
				'label'       => 'Webhook URL',
				'placeholder' => 'https://hooks.slack.com/services/xxx/yyy/zzz',
				'description' => 'Create an Incoming Webhook in your Slack workspace settings',
			],
			[
				'type'        => 'text',
				'name'        => 'channel',
				'label'       => 'Channel',
				'placeholder' => '#notifications (optional — uses webhook default)',
			],
			[
				'type'        => 'number',
				'name'        => 'cooldown',
				'label'       => 'Cooldown (seconds)',
				'description' => 'Minimum seconds between identical notifications (default: 3600)',
				'placeholder' => '3600',
			],
		];
	}
}
