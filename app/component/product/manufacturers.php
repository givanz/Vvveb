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

namespace Vvveb\Component\Product;

use Vvveb\System\Component\ComponentBase;
use Vvveb\System\Event;
use function Vvveb\url;

class Manufacturers extends ComponentBase {
	public static array $defaultOptions = [
		'start'                    => 0,
		'count'                    => ['url', 4],
		'manufacturer_id'          => null,
		'order'                    => ['url', 'price asc'],
		'category_id'              => null,
		'limit'                    => 7,
		'page'                     => 1,
		'type'                     => 'tags',
		'parents_only'             => false,
		'parents_children_only'    => false,
		'parents_without_children' => false,
		'filter'                   => null,
	];

	public int $cacheExpire = 0;

	/**
	 * Get manufacturers list with active filter state and URLs.
	 *
	 * @return array<string, mixed> Manufacturers data with manufacturer list and active flags.
	 */
	public function results(): array {
		$category = new \Vvveb\Sql\ManufacturerSQL();
		$results  = $category->getAll($this->options);

		$filter = [];

		if (isset($this->options['filter'])) {
			$filter = $this->options['filter']['manufacturer_id'] ?? [];
		}

		foreach ($results['manufacturer'] as &$manufacturer) {
			if ($filter) {
				$manufacturer['active'] = in_array($manufacturer['manufacturer_id'], $filter);
			}
			$manufacturer['url'] = url('product/manufacturer/index', $manufacturer);
		}

		[$results] = Event :: trigger(__CLASS__, __FUNCTION__, $results);

		return $results;
	}
}
