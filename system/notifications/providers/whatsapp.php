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
 * WhatsApp Business API notification provider.
 */
class Whatsapp implements Provider {
	/**
	 * Send a WhatsApp message notification.
	 *
	 * @param string                  $event    The notification event name
	 * @param array<array-key, mixed> $data     The full notification envelope
	 * @param array<array-key, mixed> $settings Provider settings (api_url, token, from)
	 *
	 * @return bool
	 */
	public static function send(string $event, array $data, array $settings): bool {
		$recipient = $data['recipient'] ?? [];
		$phone     = $recipient['phone'] ?? '';

		if (!$phone) {
			return false;
		}

		$apiUrl = $settings['api_url'] ?? '';
		$token  = $settings['token'] ?? '';
		$from   = $settings['from'] ?? '';

		if (!$apiUrl || !$token) {
			return false;
		}

		$subject = $data['subject'] ?? $event;
		$message = $data['message'] ?? $subject;

		// Normalize phone number (remove spaces, dashes)
		$phone = preg_replace('/[^\d+]/', '', $phone);

		// WhatsApp Business API payload (v16+ Cloud API format)
		$payload = [
			'messaging_product' => 'whatsapp',
			'to'                => $phone,
			'type'              => 'text',
			'text'              => [
				'preview_url' => false,
				'body'        => "*$subject*\n\n$message",
			],
		];

		$headers = [
			'Authorization: Bearer ' . $token,
			'Content-Type: application/json',
		];

		$ch = curl_init($apiUrl);

		curl_setopt_array($ch, [
			CURLOPT_POST           => true,
			CURLOPT_POSTFIELDS     => json_encode($payload),
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_TIMEOUT        => 30,
			CURLOPT_HTTPHEADER     => $headers,
		]);

		$response = curl_exec($ch);
		$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		curl_close($ch);

		return $httpCode >= 200 && $httpCode < 300;
	}

	/**
	 * Settings fields for admin.
	 */
	public static function settingsFields(): array {
		return [
			[
				'type'        => 'toggle',
				'name'        => 'enabled',
				'label'       => 'Enable WhatsApp Notifications',
				'description' => 'Send notifications via WhatsApp Business API',
			],
			[
				'type'        => 'url',
				'name'        => 'api_url',
				'label'       => 'API URL',
				'placeholder' => 'https://graph.facebook.com/v17.0/YOUR_PHONE_ID/messages',
				'description' => 'WhatsApp Cloud API endpoint URL',
			],
			[
				'type'        => 'password',
				'name'        => 'token',
				'label'       => 'API Token',
				'placeholder' => 'Your WhatsApp Business API token',
			],
			[
				'type'        => 'text',
				'name'        => 'from',
				'label'       => 'From Phone Number ID',
				'placeholder' => 'Your WhatsApp Business Phone Number ID',
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
