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

use function Vvveb\session as sess;
use Vvveb\Sql\UserSQL;
use Vvveb\System\PageCache;
use Vvveb\System\Session;

class User extends Auth {
	private static $namespace = 'user';

	public static function add($data) {
		$user = new UserSQL();

		if (! isset($data['username']) || ! $data['username']) {
			return false;
		}

		//check if email or username is already registered
		$check = ['email'=> $data['email']];

		if (isset($data['username'])) {
			$check['username'] = $data['username'];
		}

		if ($userInfo = $user->get($check)) {
			return $userInfo;
		}

		if (empty($data['password'])) {
			unset($data['password']);
		} else {
			$data['password'] = self :: password($data['password']);
		}

		$data['status'] = 1; //0

		return $user->add([self :: $namespace => $data]);
	}

	public static function update($data, $condition) {
		$user = new UserSQL();

		if (empty($data['password'])) {
			unset($data['password']);
		} else {
			$data['password'] = self :: password($data['password']);
		}
		//$data['status']   = 0;

		return $user->edit(array_merge([self :: $namespace => $data], $condition));
	}

	public static function get($data) {
		$user = new UserSQL();
		//check user email and that status is active
		$loginInfo = []; //['status' => 1];
		$userInfo  = false;

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

		$session = Session :: getInstance();
		$session->regenerateId(true);
		unset($userInfo['password']);
		$session->set(self :: $namespace, $userInfo);

		PageCache::disable(self :: $namespace);

		return $userInfo;
	}

	public static function logout() {
		PageCache::enable(self :: $namespace);

		return sess([self :: $namespace => false]);
	}

	public static function hash($data, $salt) {
		return hash_hmac('md5', $data, $salt);
	}

	public static function generateCookie($cookieValue) {
		list($value, $hash) = explode(':', $cookieValue, 2);

		if (md5($value . '-' . SECRET_KEY) == $hash) {
			return true;
		} else {
			return false;
		}
	}

	public static function checkCookie($cookieValue, $hmac, $scheme) {
		list($username, $hash, $expiration, $token) = explode('|', $cookieValue, 4);

		$key = hash($username . '|' . $hash . '|' . $expiration . '|' . $token, $scheme);

		$algo = function_exists('hash') ? 'sha256' : 'sha1';
		$hash = hash_hmac($algo, $username . '|' . $expiration . '|' . $token, $key);

		$valid = hash_equals($hash, $hmac);

		return $valid;
	}

	/**
	 * @ Currently logged in user data or empty array if guest
	 * @return mixed 
	 */
	public static function current() {
		$current = sess(self :: $namespace, []);

		if ($current) {
			PageCache::disable('user');
		} else {
			PageCache::enable('user');
		}

		return $current;
	}

	/**
	 * @ Update user session data
	 * @param mixed $data 
	 *
	 * @return mixed 
	 */
	public static function session($data) {
		$current = self :: current();

		if ($current && $data && is_array($data)) {
			$current = array_merge($current, $data);

			return sess([self :: $namespace => $current]);
		}

		return false;
	}
}
