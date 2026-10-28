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
use Vvveb\Sql\AdminSQL;
use Vvveb\System\PageCache;
use Vvveb\System\Session;

class Admin extends Auth {
	/**
	 * Session namespace key for admin data.
	 *
	 * @var string
	 */
	private static string $namespace = 'admin';

	/**
	 * Prepare admin data for storage: hash password and encode site_access.
	 *
	 * @param array<string, mixed> $data Reference to the admin data array.
	 *
	 * @return array<string, mixed> The modified data array<array-key, mixed>.
	 */
	private static function setUserData(array &$data): array {
		if (isset($data['password'])) {
			$data['password'] = self :: password($data['password']);
		}

		if (isset($data['site_access'])) {
			if (is_array($data['site_access'])) {
				$data['site_access'] = json_encode($data['site_access']);
			}
		} else {
			$data['site_access'] = '[]';
		}

		return $data;
	}

	/**
	 * Add a new admin user.
	 *
	 * @param array<string, mixed> $data Admin data including username, email, etc.
	 *
	 * @return array<array-key, mixed>|false Existing admin info if already registered, new admin record on success, or false on validation failure.
	 */
	public static function add(array $data): array|false {
		$admin = new AdminSQL();

		if (!isset($data['username']) || !$data['username']) {
			return false;
		}

		self::sanitize($data);

		//check if email or username is already registered
		$check = ['email' => $data['email']];

		if (isset($data['username'])) {
			$check['username'] = $data['username'];
		}

		if ($adminInfo = $admin->get($check)) {
			return $adminInfo;
		}

		$data['status']  = 1; //0
		$data['last_ip'] = $_SERVER['REMOTE_ADDR'] ?? '';

		self::setUserData($data);

		return $admin->add([self :: $namespace => $data]);
	}

	/**
	 * Check if the current admin has a specific capability.
	 *
	 * @param string $capability The capability name to check.
	 * @param string $app        The application context.
	 *
	 * @return bool True if the admin has the capability.
	 */
	public static function hasCapability(string $capability, string $app = APP): bool {
		$admin        = sess(self :: $namespace, false);
		$capabilities = $admin['permissions'][$app]['capabilities'] ?? $admin['permissions']['capabilities'] ?? [];

		return in_array($capability, $capabilities);
	}

	/**
	 * Get the site access list for the current admin.
	 *
	 * @return array<int|string, mixed> Array of site IDs the admin can access.
	 */
	public static function siteAccess(): array {
		$admin       = sess(self :: $namespace, false);

		return $site_access = $admin['site_access'] ?? [];
	}

	/**
	 * Check if the current admin has access to a specific site.
	 *
	 * @param int|string $site_id The site ID to check.
	 *
	 * @return bool True if the admin has access to the site.
	 */
	public static function hasSiteAccess(int|string $site_id): bool {
		$admin       = sess(self :: $namespace, false);
		$site_access = $admin['site_access'] ?? [];

		return in_array($site_id, $site_access);
	}

	/**
	 * Check if the current admin has a specific permission.
	 *
	 * @param string|array<array-key, mixed> $permission The permission identifier to check.
	 * @param string                         $app        The application context.
	 *
	 * @return array<array-key, mixed>|bool True if the admin has the permission.
	 */
	public static function hasPermission(string|array $permission, string $app = APP): array|bool {
		$admin       = sess(self :: $namespace, false);

		if (!$admin) {
			return false;
		}

		$permissions = ($admin['permissions'][$app] ?? $admin['permissions']) ?: [];
		$allow       = $permissions['allow'] ?? [];
		$deny        = $permissions['deny'] ?? [];

		return Role::has($permission, $allow, $deny, $admin['role_id']);
	}

	/**
	 * Update an existing admin record.
	 *
	 * @param array<string, mixed> $data      The data to update.
	 * @param array<string, mixed> $condition The WHERE conditions for the update.
	 *
	 * @return mixed The result of the update operation.
	 */
	public static function update(array $data, array $condition): mixed {
		$admin = new AdminSQL();

		self::setUserData($data);
		self::sanitize($data);

		$data['updated_at'] ??= date('Y-m-d H:i:s', time());

		return $admin->edit(array_merge([self :: $namespace => $data], $condition));
	}

	/**
	 * Retrieve admin info by login credentials or identifiers.
	 *
	 * @param array<string, mixed> $data Lookup criteria (email, username, token, etc.).
	 *
	 * @return array<string, mixed> Admin info array, or empty array if not found.
	 */
	public static function get(array $data): array {
		$loginInfo = []; //['status' => 1];
		$adminInfo = false;

		foreach (['email', 'user', 'username', 'role_id', 'admin_id', 'token', 'admin_auth_token', 'status'] as $key) {
			if (isset($data[$key])) {
				$loginInfo[$key] = $data[$key];
			}
		}

		if ($loginInfo) {
			$admin     = new AdminSQL();
			$adminInfo = $admin->get($loginInfo);

			if (isset($adminInfo['permissions'])) {
				$adminInfo['permissions'] = json_decode($adminInfo['permissions'], true);
			}

			if (isset($adminInfo['site_access'])) {
				$adminInfo['site_access'] = json_decode($adminInfo['site_access'], true);
			}
		}

		if (!$adminInfo) {
			return [];
		}

		return $adminInfo;
	}

	/**
	 * Authenticate an admin using an auth token.
	 *
	 * @param string               $admin_auth_token The authentication token.
	 * @param array<string, mixed> $additionalInfo   Additional data to store in the session.
	 * @param array<string, mixed> &$feedback        Feedback message and code on failure.
	 *
	 * @return array<string, mixed>|false Admin info on success, false on failure.
	 */
	public static function auth(string $admin_auth_token, array $additionalInfo = [], array &$feedback = []): array|false {
		//check admin email and that status is active
		$data['status']           = 1;
		$data['admin_auth_token'] = $admin_auth_token;
		$adminInfo                = self::get($data);
		$userExists               = ($adminInfo && isset($adminInfo['password']));
		$passwordCorrect          = false;

		if ($userExists) {
			$passwordCorrect = hash_equals($data['admin_auth_token'], $admin_auth_token);
		}

		if (!$userExists || !$passwordCorrect) {
			if ($userExists) {
				$message = __('Token incorrect!');
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
		unset($adminInfo['password']);
		$session->set(self :: $namespace, $adminInfo + $additionalInfo);

		PageCache::disable('user');

		return $adminInfo;
	}

	/**
	 * Authenticate an admin using email/username and password.
	 *
	 * @param array<string, mixed> $data           Login credentials (email/username, password).
	 * @param array<string, mixed> $additionalInfo Additional data to store in the session.
	 * @param array<string, mixed> &$feedback      Feedback message and code on failure.
	 *
	 * @return array<string, mixed>|false Admin info on success, false on failure.
	 */
	public static function login(array $data, array $additionalInfo = [], array &$feedback = []): array|false {
		//check admin email and that status is active
		$data['status']  = 1;
		$adminInfo       = self::get($data);
		$passwordCorrect = false;
		$userExists      = ($adminInfo && isset($adminInfo['password']));

		if ($userExists) {
			$passwordCorrect = self::checkPassword($data['password'] ?? '', $adminInfo['password']);
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
		unset($adminInfo['password']);
		$session->set(self :: $namespace, $adminInfo + $additionalInfo);
		$lastIp        = $_SERVER['REMOTE_ADDR'] ?? '';
		self::update(['last_ip' => $lastIp], ['admin_id' => $adminInfo['admin_id']]);

		PageCache::disable('user');

		return $adminInfo;
	}

	/**
	 * Log out the current admin by clearing session data and enabling page cache.
	 *
	 * @return void
	 */
	public static function logout(): void {
		PageCache::enable('user');

		sess([self :: $namespace => []]);
	}

	/**
	 * Get the currently logged-in admin's session data.
	 *
	 * @return array<string, mixed> Current admin session data, or empty array if not logged in.
	 */
	public static function current(): array {
		$current = sess(self :: $namespace, []);

		if ($current) {
			PageCache::disable('admin');
		} else {
			PageCache::enable('admin');
		}

		return $current ?? [];
	}

	/**
	 * Update the current admin's session data by merging new values.
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
