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

namespace Vvveb\System\User;

use function Vvveb\sanitizeHTML;
use function Vvveb\stripChars;

class Auth {
	/**
	 * Password hashing options.
	 *
	 * @var array{cost: int}
	 */
	private static array $options = ['cost' => 11];

	/**
	 * Sanitize user data by stripping unwanted characters and validating fields.
	 *
	 * @param array<string, mixed> $data Reference to the data array to sanitize.
	 *
	 * @return void
	 */
	public static function sanitize(array &$data): void {
		foreach ($data as $name => &$val) {
			if (is_string($val) && !in_array($name, ['created_at', 'updated_at', 'bio'])) {
				$val = stripChars($val);
			}
		}

		if (isset($data['username'])) {
			$data['username'] = preg_replace('/[^\w-]/', '', $data['username']);
		}

		if (isset($data['bio'])) {
			$data['bio'] = sanitizeHTML($data['bio']);
		}

		if (isset($data['email'])) {
			$email = filter_var($data['email'], FILTER_VALIDATE_EMAIL);

			if ($email === false) {
				unset($data['email']);
			} else {
				$data['email'] = $email;
			}
		}
	}

	/**
	 * Verify a password against a hash, rehashing if needed.
	 *
	 * @param string $password The plain-text password to verify.
	 * @param string $hash     The stored password hash.
	 *
	 * @return string|bool New hash string if rehash was needed, true if valid, false otherwise.
	 */
	public static function checkPassword(string $password, string $hash): string|bool {
		if (password_verify($password, $hash)) {
			// Check if a newer hashing algorithm is available
			// or the cost has changed
			if (password_needs_rehash($hash, PASSWORD_DEFAULT, self :: $options)) {
				// If so, create a new hash, and replace the old one
				return $newHash = password_hash($password, PASSWORD_DEFAULT, self :: $options);
			}

			return true;
		}

		return false;
	}

	/**
	 * Generate a password hash using the configured options.
	 *
	 * @param string $password The plain-text password to hash.
	 *
	 * @return string The resulting password hash.
	 */
	public static function password(string $password): string {
		return password_hash($password, PASSWORD_DEFAULT, self :: $options);
	}
}
