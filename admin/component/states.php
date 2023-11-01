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

use function Vvveb\session;
use Vvveb\System\Component\ComponentBase;
use Vvveb\System\Event;
use Vvveb\System\Session;
use Vvveb\System\Sites;

class States extends ComponentBase {
	public static $defaultOptions = [
	];

	protected $options = [];

	public $cacheExpire = 0; //seconds

	function cacheKey() {
		//disable caching
		return false;
	}

	function results() {
		$states                 = Sites::getStates();
		$active                 = session('state') ?? 'live';
		$results['active_name'] = $states[$active]['name'];
		$results['active_icon'] = $states[$active]['icon'];

		$results['states'] = $states;
		$results['active'] = $active;

		list($results) = Event :: trigger(__CLASS__,__FUNCTION__, $results);

		return $results;
	}
}
