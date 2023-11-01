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

use Vvveb\Sql\UserSQL;
use Vvveb\System\Component\ComponentBase;
use Vvveb\System\Event;
use Vvveb\System\Images;

class Users extends ComponentBase {
	public static $defaultOptions = [
		'start'           => 0,
		'limit'           => 10,
		'site_id'         => null,
		'user_id'         => 'url',
		'user'            => ['url', 'price asc'],
		'id'              => NULL,
	];

	public $options = [];

	function results() {
		$users = new UserSQL();

		$results = $users->getAll($this->options);

		if (isset($results['user'])) {
			foreach ($results['user'] as $id => &$user) {
				if (isset($user['images'])) {
					$user['images'] = json_decode($user['images'], 1);

					foreach ($user['images'] as &$image) {
						$image = Images::image('user', $image);
					}
				}

				if (isset($user['image'])) {
					$user['images'][] = Images::image($user['image'], 'user');
				}
			}
		}

		list($results) = Event :: trigger(__CLASS__,__FUNCTION__, $results);

		return $results;
	}
}
