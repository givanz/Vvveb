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
 * Webhook notification provider — sends POST/PUT/GET requests to user-defined endpoints.
 *
 * Multiple named webhook endpoints can be defined. Each defined webhook
 * becomes a selectable provider option per event (e.g. "My CRM webhook").
 * The per-event routing selects which named webhooks to fire.
 */
class Webhooks implements Provider {
	/**
	 * Send a webhook notification to a specific endpoint.
	 *
	 * For per-event routing, $settings['webhooks'] should contain the named webhooks,
	 * and $data['data']['_webhook_name'] identifies which webhook to fire.
	 *
	 * @param string                  $event    The notification event name
	 * @param array<array-key, mixed> $data     The full notification envelope
	 * @param array<array-key, mixed> $settings Provider settings including 'webhooks' array
	 *
	 * @return bool
	 */
	public static function send(string $event, array $data, array $settings): bool {
		$webhooks = $settings['webhooks'] ?? [];
		$target   = $data['data']['_webhook_name'] ?? '';
		// Per-event selection: list of globally defined webhook names and/or free-form URLs
		$selected = array_filter((array)($settings['_selected'] ?? []), fn ($s) => $s !== '');
		unset($settings['_selected']);

		if (!$webhooks && !$selected) {
			return false;
		}

		if (!empty($selected)) {
			// Send only to the webhooks selected for this event; entries that are not
			// names of defined webhooks are treated as ad-hoc endpoint URLs
			$names     = [];
			$extraUrls = [];

			foreach ($selected as $sel) {
				if (str_starts_with($sel, 'http://') || str_starts_with($sel, 'https://')) {
					$extraUrls[] = ['url' => $sel, 'method' => 'POST', 'enabled' => true];
				} else {
					$names[] = $sel;
				}
			}

			$success = true;
			$sent    = false;

			foreach ($webhooks as $wh) {
				if (in_array($wh['name'] ?? '', $names, true) && !empty($wh['enabled'])) {
					$result = self::sendToWebhook($wh, $event, $data);
					$sent   = true;

					if (!$result) {
						$success = false;
					}
				}
			}

			foreach ($extraUrls as $wh) {
				$result = self::sendToWebhook($wh, $event, $data);
				$sent   = true;

				if (!$result) {
					$success = false;
				}
			}

			return $sent ? $success : false;
		}

		// If a specific webhook is targeted, send only to that one
		if ($target) {
			foreach ($webhooks as $wh) {
				if (($wh['name'] ?? '') === $target && !empty($wh['enabled'])) {
					return self::sendToWebhook($wh, $event, $data);
				}
			}

			return false;
		}

		// Otherwise send to all enabled webhooks
		$success = true;

		foreach ($webhooks as $wh) {
			if (!empty($wh['enabled'])) {
				$result = self::sendToWebhook($wh, $event, $data);

				if (!$result) {
					$success = false;
				}
			}
		}

		return $success;
	}

	/**
	 * Send a single webhook request.
	 *
	 * @param array<array-key, mixed> $webhook
	 * @param string                  $event
	 * @param array<array-key, mixed> $data
	 */
	private static function sendToWebhook(array $webhook, string $event, array $data): bool {
		$url     = $webhook['url'] ?? '';
		$method  = strtoupper($webhook['method'] ?? 'POST');
		$headers = $webhook['headers'] ?? [];

		if (!$url) {
			return false;
		}

		// Build payload
		$payload = $data;
		unset($payload['data']['_webhook_name']); // internal routing key, not for external use

		// Convert headers string to array
		$headerArr = ['Content-Type: application/json'];

		if (is_string($headers)) {
			// Parse "Key: Value\nKey2: Value2" format
			$lines = array_filter(array_map('trim', explode("\n", $headers)));

			foreach ($lines as $line) {
				if (str_contains($line, ':')) {
					$headerArr[] = $line;
				}
			}
		} elseif (is_array($headers)) {
			foreach ($headers as $key => $value) {
				$headerArr[] = "$key: $value";
			}
		}

		$jsonPayload = json_encode($payload);

		$ch = curl_init($url);

		curl_setopt_array($ch, [
			CURLOPT_CUSTOMREQUEST  => $method,
			CURLOPT_POSTFIELDS     => $jsonPayload,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_TIMEOUT        => 30,
			CURLOPT_HTTPHEADER     => $headerArr,
		]);

		$response = curl_exec($ch);
		$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		curl_close($ch);

		return $httpCode >= 200 && $httpCode < 300;
	}

	/**
	 * Settings fields for admin — webhooks have a dynamic list of named endpoints.
	 */
	public static function settingsFields(): array {
		return [
			[
				'type'        => 'toggle',
				'name'        => 'enabled',
				'label'       => 'Enable Webhook Notifications',
				'description' => 'Send notifications to HTTP webhook endpoints',
			],
			[
				'type'        => 'list',
				'name'        => 'webhooks',
				'label'       => 'Webhook Endpoints',
				'description' => 'Define named webhook endpoints that can be selected per notification event',
				'fields'      => [
					[
						'type'  => 'toggle',
						'name'  => 'enabled',
						'label' => 'Enabled',
					],
					[
						'type'        => 'text',
						'name'        => 'name',
						'label'       => 'Name',
						'placeholder' => 'My Webhook',
					],
					[
						'type'        => 'url',
						'name'        => 'url',
						'label'       => 'URL',
						'placeholder' => 'https://example.com/webhook',
					],
					[
						'type'    => 'select',
						'name'    => 'method',
						'label'   => 'HTTP Method',
						'options' => [
							'POST'  => 'POST',
							'PUT'   => 'PUT',
							'PATCH' => 'PATCH',
							'GET'   => 'GET',
						],
					],
					[
						'type'        => 'textarea',
						'name'        => 'headers',
						'label'       => 'Custom Headers',
						'placeholder' => "X-Api-Key: my-key\nX-Custom: value",
						'description' => 'One header per line, format: Key: Value',
					],
				],
			],
		];
	}
}
