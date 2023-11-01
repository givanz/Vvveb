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

use Vvveb\Controller\Base;

class Taxonomy extends Base {
	function index() {
		$view        = $this->view;
		$taxonomies  = new \Vvveb\Sql\taxonomySQL();
		$limit       = 10000;

		$options = [
			'limit'        => $limit,
		] + $this->global;

		$results = $taxonomies->getAll($options);

		if ($results) {
			foreach ($results as &$category) {
			}
		}
		$view->taxonomies = $results;
	}
}
