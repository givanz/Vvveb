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

class Manufacturers extends ComponentBase {
	public static $defaultOptions = [
		'start'                    => 0,
		//'language_id'              => 1,
		'count'                    => ['url', 4],
		'id_manufacturer'          => NULL,
		'order'                    => ['url', 'price asc'],
		'id_category'              => NULL,
		'limit'                    => 7,
		'page'                     => 1,
		'type'                     => 'tags',
		'parents_only'             => false,
		'parents_children_only'    => false,
		'parents_without_children' => false,
	];

	function results() {
		$category = new \Vvveb\Sql\ManufacturerSQL();
		$results  = $category->getAll($this->options);

		list($results) = Event :: trigger(__CLASS__,__FUNCTION__, $results);

		return $results;
	}
}
