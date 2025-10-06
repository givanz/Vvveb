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

class Admins extends ComponentBase {
	public static $defaultOptions = [
		'start'  => 0,
		'limit'  => ['url', 4],
		'status' => 1,
	];

	public $options = [];

	function results() {
		$admins = new \Vvveb\Sql\AdminSQL();

		$results = $admins->getAll($this->options);

		if (isset($results['admin'])) {
			foreach ($results['admin'] as $id => &$admin) {
				if (isset($admin['avatar'])) {
					$admin['avatar_url'] = Images::image($admin['avatar'], 'admin');
				}
				unset($admin['password']);
				$admin['url'] = url('content/user/index', ['admin_id' => $admin['admin_id']]);
			}
		}

		list($results) = Event :: trigger(__CLASS__,__FUNCTION__, $results);

		return $results;
	}
}
