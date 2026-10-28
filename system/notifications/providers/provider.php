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
 * Interface that all notification providers must implement.
 */
interface Provider {
	/**
	 * Send a notification for the given event.
	 *
	 * @param string                  $event    The notification event name
	 * @param array<array-key, mixed> $data     The full notification envelope
	 * @param array<array-key, mixed> $settings Provider-specific settings from config
	 *
	 * @return bool True on success, false on failure
	 */
	public static function send(string $event, array $data, array $settings): bool;

	/**
	 * Return field definitions for the admin settings tab.
	 *
	 * @return list<array{type: string, name: string, label: string, description?: string, placeholder?: string}>
	 */
	public static function settingsFields(): array;
}
