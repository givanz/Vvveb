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

class Auth {
	static $options = ['cost' => 11];

	public static function checkPassword($password, $hash) {
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

	public static function password($password) {
		return password_hash($password, PASSWORD_DEFAULT, self :: $options);
	}
}
