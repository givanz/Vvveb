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

namespace Vvveb\Component\Content;

use Vvveb\Sql\CategorySQL;
use Vvveb\System\Component\ComponentBase;
use Vvveb\System\Event;

class Categories extends ComponentBase {
	public static $defaultOptions = [
		'start'                      => 0,
		'limit'                      => 7,
		'site_id'                    => NULL,
		'language_id'                => NULL,
		'taxonomy_id'                => NULL,
		'post_id'                    => 'url',
		'parent_id'                  => NULL,
		'search'                     => NULL,
		'type'                       => 'categories',
		'post_type'                  => 'post',
	];

	function results() {
		$category = new CategorySQL();

		$results  = $category->getCategories($this->options);

		list($results) = Event :: trigger(__CLASS__,__FUNCTION__, $results);

		return $results;
	}
}
