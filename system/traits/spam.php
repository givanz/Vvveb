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

namespace Vvveb\System\Traits;

trait Spam {
	/**
	 * Hidden field names used as spam traps.
	 *
	 * These must be empty in legitimate submissions; bots typically fill them.
	 *
	 * @var list<string>
	 */
	protected array $spamFields = [
		'firstname-empty',
		'lastname-empty',
		'subject-empty',
		'contact-form',
	];

	/**
	 * Check if a message is spam (stub — override in subclass for real logic).
	 *
	 * @param array<string, mixed> $message The submitted message data.
	 *
	 * @return array<string, mixed> The message data, potentially filtered.
	 */
	public function checkIfSpam(array &$message): array {
		return $message;
	}

	/**
	 * Determine whether a submitted message contains spam honeypot data.
	 *
	 * @param array<string, mixed> $message The submitted message data.
	 *
	 * @return bool True if spam is detected, false otherwise.
	 */
	public function isSpam(array &$message): bool {
		foreach ($this->spamFields as $field) {
			if (!empty($message[$field])) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Remove honeypot fields from a message array.
	 *
	 * @param array<string, mixed> $message The submitted message data.
	 *
	 * @return array<string, mixed> The message data with spam trap fields removed.
	 */
	public function removeSpamCatchFields(array &$message): array {
		foreach ($this->spamFields as $field) {
			if (isset($message[$field])) {
				unset($message[$field]);
			}
		}

		return $message;
	}
}
