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

namespace Vvveb\Component;

use Vvveb\System\Component\ComponentBase;
use Vvveb\System\Event;
use Vvveb\System\Images;
use function Vvveb\url;

class Users extends ComponentBase {
	public static $defaultOptions = [
		'start'  => 0,
		'limit'  => ['url', 4], //default get from url, if missing it will use a default of 4
		'status' => 1, //1 enabled, 0 disabled
		'search' => 1, //search in username and first and last names
		'user_id' => 1, //array list of user_id's to filter
	];

	public $options = [];

	function results() {
		$users = new \Vvveb\Sql\UserSQL();

		$results = $users->getAll($this->options);

		if (isset($results['user'])) {
			foreach ($results['user'] as $id => &$user) {
				if (isset($user['avatar'])) {
					$user['avatar_url'] = Images::image($user['avatar'], 'user');
				}
				unset($user['password']);
				$user['url'] = url('content/user/index', ['user_id' => $user['user_id']]);
			}
		}

		list($results) = Event :: trigger(__CLASS__,__FUNCTION__, $results);

		return $results;
	}
}
