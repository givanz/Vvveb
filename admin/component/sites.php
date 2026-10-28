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
use Vvveb\System\Sites as SitesList;
use Vvveb\System\User\Admin;

class Sites extends ComponentBase {
	/** @var array{limit: int, page: int, site_access: int} Default options for site listing. */
	public static array $defaultOptions = [
		'limit'       => 1000,
		'page'        => 1,
		'site_access' => 1,
	];

	/** @var array<string, mixed> Resolved options for this component. */
	protected array $options = [];

	/** @var int Cache expiry time in seconds (0 = disabled). */
	public int $cacheExpire = 0;

	/**
	 * Disable caching for this component.
	 *
	 * @return string|null Always returns false to disable caching.
	 */
	public function cacheKey(): ?string {
		//disable caching
		return null;
	}

	/**
	 * Get all sites filtered by admin site access, with active site and states.
	 *
	 * @return array{sites: array<int, mixed>, states: array<string, mixed>, site_id: mixed, active: array<string, mixed>, count: int}
	 */
	public function results(): array {
		$results = [];

		$results['sites']    = SitesList::getSites();
		$results['states']   = SitesList::getStates();
		$results['site_id']  = self :: $global['site_id'];
		$results['active']   = SitesList::getSiteById($results['site_id']);
		$results['count']    = count($results['sites']);

		if ($this->options['site_access']) {
			$site_access = Admin::siteAccess();

			if ($site_access) {
				foreach ($results['sites'] as $key => $site) {
					if (!in_array($site['site_id'], $site_access)) {
						unset($results['sites'][$key]);
					}
				}
			}
		}

		[$results] = Event::trigger(__CLASS__, __FUNCTION__, $results);

		return $results;
	}
}
