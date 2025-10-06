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
use Vvveb\System\User\User as UserClass;
use function Vvveb\url;

class User extends ComponentBase {
	public static $defaultOptions = [
		'user_id'  => null,
		'username' => null,
	];

	protected $options = [];

	public $cacheExpire = 0; //seconds

	function cacheKey() {
		//disable caching
		return false;
	}

	function results() {
		if (isset($this->options['user_id']) || isset($this->options['username'])) {
			$users = new \Vvveb\Sql\UserSQL();

			$results = $users->get($this->options);
		} else {
			$results = UserClass::current();
		}

		if ($results) {
			unset($results['password'], $results['token']);

			foreach (['avatar', 'cover'] as $image) {
				if (isset($results[$image])) {
					$results[$image . '_url']= Images::image($results[$image], 'user');
				}
			}

			//$results['url'] = url('content/user/index', ['username' => $results['username']]);
		} else {
			$results = [];
		}

		if (! $results) {
			$results = [];
		}

		list($results) = Event :: trigger(__CLASS__,__FUNCTION__, $results);

		return $results;
	}
}
