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

use function Vvveb\session as sess;
use Vvveb\System\Component\ComponentBase;
use Vvveb\System\Event;
use Vvveb\System\Sites as SitesList;
use Vvveb\System\User\Admin;

class Sites extends ComponentBase {
	public static $defaultOptions = [
		'limit'       => 1000,
		'page'        => 1,
		'site_access' => 1,
	];

	protected $options = [];

	public $cacheExpire = 0; //seconds

	function cacheKey() {
		//disable caching
		return false;
	}

	function results() {
		$results = [];

		$results['sites']    = SitesList::getSites();
		$results['states']   = SitesList::getStates();
		$results['site_id']  = sess('site_id');
		$results['active']   = SitesList::getSiteById($results['site_id']);
		$results['count']    = count($results['sites']);

		if ($this->options['site_access']) {
			$site_access = Admin::siteAccess();

			if ($site_access) {
				foreach ($results['sites'] as $key => $site) {
					if (! in_array($site['id'], $site_access)) {
						unset($results['sites'][$key]);
					}
				}
			}
		}

		list($results) = Event :: trigger(__CLASS__,__FUNCTION__, $results);

		return $results;
	}
}
