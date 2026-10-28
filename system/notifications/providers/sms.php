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
 * SMS notification provider — sends via HTTP API (Twilio-style).
 */
class Sms implements Provider {
	/**
	 * Send an SMS notification.
	 *
	 * @param string                  $event    The notification event name
	 * @param array<array-key, mixed> $data     The full notification envelope
	 * @param array<array-key, mixed> $settings Provider settings (sid, token, from)
	 *
	 * @return bool
	 */
	public static function send(string $event, array $data, array $settings): bool {
		$recipient = $data['recipient'] ?? [];
		$phone     = $recipient['phone'] ?? '';

		if (!$phone) {
			return false;
		}

		$sid    = $settings['sid'] ?? '';
		$token  = $settings['token'] ?? '';
		$from   = $settings['from'] ?? '';

		if (!$sid || !$token || !$from) {
			return false;
		}

		// Use message body or fall back to subject
		$message = $data['message'] ?? $data['subject'] ?? $event;

		// Trim to 1600 chars for SMS (concatenated SMS limit)
		$message = mb_substr($message, 0, 1600);

		$apiUrl = "https://api.twilio.com/2010-04-01/Accounts/$sid/Messages.json";

		$body = http_build_query([
			'To'   => $phone,
			'From' => $from,
			'Body' => $message,
		]);

		$ch = curl_init($apiUrl);

		curl_setopt_array($ch, [
			CURLOPT_POST           => true,
			CURLOPT_POSTFIELDS     => $body,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_TIMEOUT        => 30,
			CURLOPT_USERPWD        => "$sid:$token",
			CURLOPT_HTTPHEADER     => ['Content-Type: application/x-www-form-urlencoded'],
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
				'label'       => 'Enable SMS Notifications',
				'description' => 'Send notifications via SMS',
			],
			[
				'type'        => 'text',
				'name'        => 'sid',
				'label'       => 'Account SID',
				'placeholder' => 'Your Twilio Account SID',
			],
			[
				'type'        => 'password',
				'name'        => 'token',
				'label'       => 'Auth Token',
				'placeholder' => 'Your Twilio Auth Token',
			],
			[
				'type'        => 'text',
				'name'        => 'from',
				'label'       => 'From Number',
				'placeholder' => '+1234567890',
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
