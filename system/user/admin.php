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

use \Vvveb\Sql\AdminSQL;
use Vvveb\System\PageCache;

class Admin extends Auth {
	public static function add($data) {
		$admin = new \Vvveb\Sql\AdminSQL();

		//check if email is already registerd
		if ($adminInfo = $admin->get(['email'=> $data['email']])) {
			return true;
		}

		$data['password'] = self :: password($data['password']);
		$data['status']   = 0;

		return $admin->add(['admin' => $data]);
	}

	public static function hasPermission($permission) {
		$admin       = \Vvveb\session('admin', false);
		$permissions = $admin['permissions'] ?: [];
		$allow       = $permissions['allow'] ?? [];
		$deny        = $permissions['deny'] ?? [];

		return Role::has($permission, $allow, $deny, $admin['role_id']);
	}

	public static function update($data, $condition) {
		$admin = new \Vvveb\Sql\AdminSQL();

		if (isset($data['password'])) {
			$data['password'] = self :: password($data['password']);
		}

		return $admin->edit(array_merge(['admin' => $data], $condition));
	}

	public static function get($data) {
		$loginInfo = []; //['status' => 1];
		$adminInfo = false;

		if (isset($data['email'])) {
			$loginInfo['email'] = $data['email'];
		}

		if (isset($data['user'])) {
			$loginInfo['username'] = $data['user'];
		}

		if (isset($data['username'])) {
			$loginInfo['username'] = $data['username'];
		}

		if (isset($data['role_id'])) {
			$loginInfo['role_id'] = $data['role_id'];
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

		if ($loginInfo) {
			$admin     = new AdminSQL();
			$adminInfo = $admin->get($loginInfo);

			if (isset($adminInfo['permissions'])) {
				$adminInfo['permissions'] = json_decode($adminInfo['permissions'], true);
			}
		}

		if (! $adminInfo) {
			return [];
		}

		return $adminInfo;
	}

	public static function login($data, $additionalInfo = []) {
		//check admin email and that status is active
		$data['status'] = 1;
		$adminInfo      = self::get($data);

		if ((! $adminInfo) ||
			! self::checkPassword($data['password'], $adminInfo['password'])) {
			return false;
		}

		unset($adminInfo['password']);
		\Vvveb\session(['admin' => $adminInfo + $additionalInfo]);

		PageCache::disable();

		return $adminInfo;
	}

	public static function logout() {
		PageCache::enable();

		return \Vvveb\session(['admin' => false]);
	}

	public static function current() {
		return \Vvveb\session('admin', []);
	}
}
