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

use Vvveb\System\PageCache;

class User extends Auth {
	public static function add($data) {
		$user = new \Vvveb\Sql\UserSQL();

		//check if email is already registerd
		if ($userInfo = $user->get(['email'=> $data['email']])) {
			return true;
		}

		if (empty($data['password'])) {
			unset($data['password']);
		} else {
			$data['password'] = self :: password($data['password']);
		}

		$data['status']   = 1; //0

		return $user->add(['user' => $data]);
	}

	public static function update($data, $condition) {
		$user = new \Vvveb\Sql\UserSQL();

		if (empty($data['password'])) {
			unset($data['password']);
		} else {
			$data['password'] = self :: password($data['password']);
		}
		//$data['status']   = 0;

		return $user->edit(array_merge(['user' => $data], $condition));
	}

	public static function get($data) {
		$user = new \Vvveb\Sql\UserSQL();
		//check user email and that status is active
		$loginInfo = []; //['status' => 1];
		$userInfo  = false;

		if (isset($data['email'])) {
			$loginInfo['email'] = $data['email'];
		}

		if (isset($data['user'])) {
			$loginInfo['username'] = $data['user'];
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

		if (! $userInfo) {
			return [];
		}

		return $userInfo;
	}

	public static function login($data) {
		$data['status'] = 1;
		$userInfo       = self::get($data);

		if (! $userInfo) {
			return null;
		}

		if (! self::checkPassword($data['password'], $userInfo['password'])) {
			return false;
		}

		unset($userInfo['password']);
		\Vvveb\session(['user' => $userInfo]);

		PageCache::disable();

		return $userInfo;
	}

	public static function logout() {
		PageCache::enable();

		return \Vvveb\session(['user' => false]);
	}

	public static function hash($data, $salt) {
		return hash_hmac('md5', $data, $salt);
	}

	public static function generateCookie() {
		list($value, $hash) = explode(':', $cookie_value, 2);

		if (md5($value . '-' . SECRET_KEY) == $hash) {
			return true;
		} else {
			return false;
		}
	}

	public static function checkCookie($cookieValue) {
		list($username, $hash, $expiration, $token) = explode('|', $cookieValue, 4);

		$key = hash($username . '|' . $hash . '|' . $expiration . '|' . $token, $scheme);

		$algo = function_exists('hash') ? 'sha256' : 'sha1';
		$hash = hash_hmac($algo, $username . '|' . $expiration . '|' . $token, $key);

		$valid = hash_equals($hash, $hmac);

		return $valid;
	}

	public static function current() {
		return \Vvveb\session('user', []);
	}
}
