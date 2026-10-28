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
 * Email notification provider — sends via the existing Vvveb email system.
 */
class Email implements Provider {
	/**
	 * Send an email notification.
	 *
	 * @param string                  $event    The notification event name
	 * @param array<array-key, mixed> $data     The full notification envelope
	 * @param array<array-key, mixed> $settings Provider settings
	 *
	 * @return bool
	 */
	public static function send(string $event, array $data, array $settings): bool {
		$recipient = $data['recipient'] ?? [];
		$to        = $recipient['email'] ?? '';

		if (!$to) {
			return false;
		}

		$subject = $data['subject'] ?? $event;
		$message = $data['message'] ?? '';
		$html    = $data['data']['html'] ?? $message;
		$txt     = $data['data']['txt'] ?? strip_tags($message);
		$siteId  = $data['data']['site_id'] ?? SITE_ID;

		return \Vvveb\email(
			$to,
			$subject,
			['html' => $html, 'txt' => $txt],
			$data['data'] ?? [],
			[],
			null,
			$siteId
		);
	}

	/**
	 * Settings fields for admin — email reuses existing settings so returns minimal fields.
	 */
	public static function settingsFields(): array {
		return [
			[
				'type'        => 'toggle',
				'name'        => 'enabled',
				'label'       => 'Enable Email Notifications',
				'description' => 'Send notifications via email using the site email settings',
			],
			[
				'type'        => 'number',
				'name'        => 'cooldown',
				'label'       => 'Cooldown (seconds)',
				'description' => 'Minimum seconds between identical notifications (0 = no cooldown)',
				'placeholder' => '0',
			],
		];
	}
}
