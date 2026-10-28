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
 * Telegram notification provider — sends messages via the Telegram Bot API.
 */
class Telegram implements Provider {
	/**
	 * Send a Telegram message.
	 *
	 * @param string                  $event    The notification event name
	 * @param array<array-key, mixed> $data     The full notification envelope
	 * @param array<array-key, mixed> $settings Provider settings (bot_token, chat_id)
	 *
	 * @return bool
	 */
	public static function send(string $event, array $data, array $settings): bool {
		$botToken = $settings['bot_token'] ?? '';
		$chatId   = $settings['chat_id'] ?? '';

		if (!$botToken || !$chatId) {
			return false;
		}

		$subject = $data['subject'] ?? $event;
		$message = $data['message'] ?? '';

		// HTML parse mode: escape content so arbitrary payloads can't break the API call
		$text = '<b>' . htmlspecialchars($subject, ENT_QUOTES, 'UTF-8', false) . '</b>';

		if ($message !== '') {
			$text .= "\n\n" . htmlspecialchars((string)$message, ENT_QUOTES, 'UTF-8', false);
		}

		// Telegram message limit is 4096 characters
		$text = mb_substr($text, 0, 4096);

		$payload = [
			'chat_id'                  => $chatId,
			'text'                     => $text,
			'parse_mode'               => 'HTML',
			'disable_web_page_preview' => true,
		];

		// Per-event chat override (e.g. different channels per notification type)
		if (!empty($data['recipient']['chat_id'])) {
			$payload['chat_id'] = $data['recipient']['chat_id'];
		}

		$apiUrl = "https://api.telegram.org/bot{$botToken}/sendMessage";

		$ch = curl_init($apiUrl);

		curl_setopt_array($ch, [
			CURLOPT_POST           => true,
			CURLOPT_POSTFIELDS     => json_encode($payload),
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_TIMEOUT        => 30,
			CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
		]);

		$response = curl_exec($ch);
		$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		curl_close($ch);

		if ($httpCode !== 200 || !is_string($response)) {
			return false;
		}

		// Telegram answers {"ok":true,...} on success
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
				'label'       => 'Enable Telegram Notifications',
				'description' => 'Send notifications via a Telegram bot',
			],
			[
				'type'        => 'password',
				'name'        => 'bot_token',
				'label'       => 'Bot Token',
				'placeholder' => '123456789:AAF...',
				'description' => 'Create a bot with @BotFather and copy its token',
			],
			[
				'type'        => 'text',
				'name'        => 'chat_id',
				'label'       => 'Chat ID',
				'placeholder' => '@channelname or -1001234567890',
				'description' => 'Target chat, group or channel ID. Add the bot to the chat first; get the ID from @userinfobot or getUpdates',
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
