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

use function Vvveb\__;
use function Vvveb\session as sess;
use Vvveb\Sql\UserSQL;
use Vvveb\System\PageCache;
use Vvveb\System\Session;

class User extends Auth {
	/**
	 * Session namespace key for user data.
	 *
	 * @var string
	 */
	private static string $namespace = 'user';

	/**
	 * Add a new user account.
	 *
	 * @param array<string, mixed> $data User data including username, email, password, etc.
	 *
	 * @return array<array-key, mixed>|false Existing user info if already registered, new user record on success, or false on validation failure.
	 */
	public static function add(array $data): array|false {
		$user = new UserSQL();

		if (!isset($data['username']) || !$data['username']) {
			return false;
		}

		self::sanitize($data);

		//check if email or username is already registered
		$check = ['username' => $data['username']];

		if (isset($data['email'])) {
			$check['email'] = $data['email'];
		}

		if ($userInfo = $user->get($check)) {
			return $userInfo;
		}

		if (empty($data['password'])) {
			unset($data['password']);
		} else {
			$data['password'] = self :: password($data['password']);
		}

		$data['status']  = 1; //0
		$data['last_ip'] = $_SERVER['REMOTE_ADDR'] ?? '';

		return $user->add([self :: $namespace => $data]);
	}

	/**
	 * Update an existing user record.
	 *
	 * @param array<string, mixed> $data      The data to update.
	 * @param array<string, mixed> $condition The WHERE conditions for the update.
	 *
	 * @return mixed The result of the update operation.
	 */
	public static function update(array $data, array $condition): mixed {
		$user = new UserSQL();

		if (empty($data['password'])) {
			unset($data['password']);
		} else {
			$data['password'] = self :: password($data['password']);
		}
		//$data['status']   = 0;
		self::sanitize($data);

		$data['updated_at'] ??= date('Y-m-d H:i:s', time());

		return $user->edit(array_merge([self :: $namespace => $data], $condition));
	}

	/**
	 * Retrieve user info by login credentials or identifiers.
	 *
	 * @param array<string, mixed> $data Lookup criteria (email, username, user_id, token, status).
	 *
	 * @return array<string, mixed> User info array, or empty array if not found.
	 */
	public static function get(array $data): array {
		$user = new UserSQL();
		//check user email and that status is active
		$loginInfo = []; //['status' => 1];

		if (isset($data['email'])) {
			$loginInfo['email'] = $data['email'];
		}

		if (isset($data[self :: $namespace])) {
			$loginInfo['username'] = $data[self :: $namespace];
		}

		if (isset($data['username'])) {
			$loginInfo['username'] = $data['username'];
		}

		if (isset($data['user_id'])) {
			$loginInfo['user_id'] = $data['user_id'];
		}

		if (isset($data['token'])) {
			$loginInfo['token'] = $data['token'];
		}

		if (isset($data['status'])) {
			$loginInfo['status'] = $data['status'];
		}

		$userInfo = $user->get($loginInfo);

		if (!$userInfo) {
			return [];
		}

		return $userInfo;
	}

	/**
	 * Authenticate a user using email/username and password.
	 *
	 * @param array<string, mixed> $data           Login credentials (email, username, password).
	 * @param array<string, mixed> $additionalInfo Additional data to store in the session.
	 * @param array<string, mixed> &$feedback      Feedback message and code on failure.
	 *
	 * @return array<string, mixed>|false User info on success, false on failure.
	 */
	public static function login(array $data, array $additionalInfo = [], array &$feedback = []): array|false {
		$data['status']  = 1;
		$userInfo        = self::get($data);
		$passwordCorrect = false;
		$userExists      = ($userInfo && isset($userInfo['password']));

		if ($userExists) {
			$passwordCorrect = self::checkPassword($data['password'] ?? '', $userInfo['password']);
		}

		if (!$userExists || !$passwordCorrect) {
			if ($userExists) {
				$message = __('Password incorrect!');
				$code    = 0;
			} else {
				$message = __('User not found or has status inactive!');
				$code    = 1;
			}

			$feedback = ['message' => $message, 'code' => $code];

			return false;
		}

		$session = Session :: getInstance();
		$session->regenerateId(true);
		unset($userInfo['password']);
		$session->set(self :: $namespace, $userInfo + $additionalInfo);

		$lastIp        = $_SERVER['REMOTE_ADDR'] ?? '';
		self::update(['last_ip' => $lastIp], ['user_id' => $userInfo['user_id']]);

		PageCache::disable('user');

		return $userInfo;
	}

	/**
	 * Log out the current user by clearing session data and enabling page cache.
	 *
	 * @return void
	 */
	public static function logout(): void {
		PageCache::enable(self :: $namespace);

		sess([self :: $namespace => false]);
	}

	/**
	 * Generate an HMAC-MD5 hash of the given data with a salt.
	 *
	 * @param string $data The data to hash.
	 * @param string $salt The salt/key for the HMAC.
	 *
	 * @return string The HMAC-MD5 hash.
	 */
	public static function hash(string $data, string $salt): string {
		return hash_hmac('md5', $data, $salt);
	}

	/**
	 * Validate a legacy cookie value by checking its MD5 hash.
	 *
	 * @param string $cookieValue The cookie value in "value:hash" format.
	 *
	 * @return bool True if the cookie hash is valid.
	 */
	public static function generateCookie(string $cookieValue): bool {
		if (!defined('SECRET_KEY')) {
			return false;
		}

		[$value, $hash] = explode(':', $cookieValue, 2);

		if (hash_equals(md5($value . '-' . SECRET_KEY), $hash)) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Validate a remember-me cookie using HMAC verification.
	 *
	 * @param string $cookieValue The cookie value in "username|hash|expiration|token" format.
	 * @param string $hmac        The HMAC signature to verify against.
	 * @param string $scheme      The hashing scheme key.
	 *
	 * @return bool True if the cookie HMAC is valid.
	 */
	/**
	 * Validate a signed login cookie.
	 *
	 * Expected format: username|expiration|token|hmac
	 * The HMAC is computed with sha256 over username|expiration|token keyed by
	 * a hash of the username, scheme and application secret.
	 */
	public static function checkCookie(string $cookieValue, string $hmac, string $scheme): bool {
		$parts = explode('|', $cookieValue);

		if (count($parts) < 3) {
			return false;
		}

		[$username, $expiration, $token] = $parts;

		$key = hash_hmac('md5', $username . '|' . $expiration . '|' . $token, $scheme);
		$h   = hash_hmac('sha256', $username . '|' . $expiration . '|' . $token, $key);

		return hash_equals($h, $hmac);
	}

	/**
	 * Get the currently logged-in user's session data.
	 *
	 * @return array<string, mixed> Current user session data, or empty array if guest.
	 */
	public static function current(): array {
		$current = sess(self :: $namespace, []);

		if ($current) {
			PageCache::disable('user');
		} else {
			PageCache::enable('user');
		}

		return $current ?: [];
	}

	/**
	 * Update the current user's session data by merging new values.
	 *
	 * @param array<string, mixed>|null $data Key-value pairs to merge into the session.
	 *
	 * @return array<array-key, mixed>|false Merged session data on success, false if no current session or invalid data.
	 */
	public static function session(?array $data): array|false {
		$current = self :: current();

		if ($current && $data) {
			$current = array_merge($current, $data);

			return sess([self :: $namespace => $current], $current);
		}

		return false;
	}
}
