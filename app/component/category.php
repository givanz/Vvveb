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

class Category extends ComponentBase {
	public static $defaultOptions = [
		'start'           => 0,
		'language_id'     => 1,
		'site_id'         => 1,
		'count'           => ['url', 7],
		'id_manufacturer' => NULL,
		'order'           => ['url', 'price asc'],
		'id_category'     => 'url',
		'slug'            => 'url',
	];

	public $cacheExpire = 0; //seconds

	function __construct($class = __CLASS__) {
		return parent::__construct($class);
	}

	function cacheKey() {
		//disable caching
		return false;
	}

	function results() {
		$product = new \Vvveb\Sql\CategorySQL();
		$results = $product->getCategory($this->options);

		//$_REQUEST['taxonomy_item_id'] = $results['taxonomy_item_id'];

		list($results) = Event :: trigger(__CLASS__,__FUNCTION__, $results);

		return $results;
	}
}
