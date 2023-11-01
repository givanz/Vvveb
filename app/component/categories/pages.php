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

namespace Vvveb\Component\Categories;

use Vvveb\System\Component\ComponentBase;
use Vvveb\System\Event;

class Pages extends ComponentBase {
	public static $defaultOptions = [
		'start'                    => 0,
		'count'                    => ['url', 100],
		'id_manufacturer'          => NULL,
		'order'                    => ['url', 'price asc'],
		'taxonomy_item_id'         => NULL,
		'limit'                    => 7,
		'page'                     => 1,
		'parents_only'             => false,
		'parents_children_only'    => false,
		'parents_without_children' => false,
	];

	function results() {
		$product = new \Vvveb\Sql\CategorySQL();
		$results = $product->getCategoriesPages($this->options);

		$current_category_id   = 5;
		$current_category_slug = 'cameras';

		$module = \Vvveb\getModuleName();

		switch ($module) {
			case 'content/post':
			break;

			case 'content/category':
				if ($this->options['taxonomy_item_id'] == 'page') {
				}

			break;
		}

		//count the number of child categories (subcategories) for each category
		if (isset($results['categories'])) {
			foreach ($results['categories'] as $taxonomy_item_id => &$category) {
				$parent_id = $category['parent_id'] ?? false;

				if ($current_category_slug == ($category['slug'] ?? '')) {
					$category['active'] = true;
				} else {
					$category['active'] = false;
				}

				if (! isset($category['children'])) {
					$category['children'] = 0;
				}

				if (isset($category['posts']) && $category['posts'] && $category['posts'][0] = '[') {
					$category['posts'] = json_decode($category['posts'], true);

					if (is_array($category['posts'])) {
						$category['posts_count'] = count($category['posts']);
					}
				}

				if ($parent_id > 0 && isset($results['categories'][$parent_id])) {
					$parent = &$results['categories'][$parent_id];

					if (isset($parent['children'])) {
						$parent['children']++;
					} else {
						$parent['children'] = 1;
					}
				}
			}
		}

		list($results) = Event :: trigger(__CLASS__,__FUNCTION__, $results);

		return $results;
	}
}
