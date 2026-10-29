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

namespace Vvveb\Controller\Content;

use Vvveb\Sql\SiteSQL;
use Vvveb\System\User\Admin;

trait SitesTrait {
	/**
	 * Returns a list of sites, optionally marking selected ones.
	 *
	 * @param array<int> $selectedSites Array of selected site IDs.
	 *
	 * @return array<int, array<string, mixed>> List of site records.
	 */
	public function sites(array $selectedSites = []): array {
		$sites = new SiteSQL();

		$options = [];

		if (Admin::hasCapability('edit_other_sites')) {
			//unset($options['site_id']);
		} else {
			$options['site_id'] = Admin::siteAccess();
		}

		$results = $sites->getAll(
			$options + [
				'start' => 0,
				'limit' => 100,
			]
		)['site'] ?? [];

		if ($results && $selectedSites) {
			foreach ($results as &$site) {
				$site['selected'] = in_array($site['site_id'], $selectedSites);
			}
		}

		return $results;
	}
}
