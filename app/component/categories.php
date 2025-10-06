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
use Vvveb\System\Images;
use function Vvveb\url;

class Categories extends ComponentBase {
	public static $defaultOptions = [
		'start'                    => 0,
		'limit'                    => 7,
		'site_id'                  => NULL,
		'order'                    => ['url', 'price asc'],
		'taxonomy_id'              => NULL,
		'post_id'                  => NULL,
		'parent_id'                => NULL,
		'search'                   => NULL,
		'type'                     => 'categories',
		'post_type'                => NULL,
		'parents_only'             => false,
		'parents_children_only'    => false,
		'parents_without_children' => false,
	];

	function results() {
		$category = new \Vvveb\Sql\CategorySQL();
		$results  = $category->getCategories($this->options);

		//count the number of child categories (subcategories) for each category
		if (isset($results['categories'])) {
			foreach ($results['categories'] as $taxonomy_item_id => &$category) {
				$parent_id = $category['parent_id'] ?? false;

				if (! isset($category['children'])) {
					$category['children'] = 0;
				}

				if (isset($category['post_type'])) {
					$category['type'] = $category['post_type'];
				}

				$category['url'] = url('content/category/index', $category);

				if (isset($category['image'])) {
					$category['image_url'] = Images::image($category['image'], 'taxonomy_item');
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

	//called on each request
	function request(&$results, $index = 0) {
		$module     = \Vvveb\getModuleName();
		$categoryId = false;

		switch ($module) {
			case 'product/category':
				$categoryId = $this->request->get['category_id'] ?? '';

			break;
		}

		if (isset($results['categories']) && $categoryId) {
			$categories = &$results['categories'];
			//traverse array in reverse to also set parents as active
			$category = end($categories);

			while ($category !== false) {
				if ($categoryId == $category['taxonomy_item_id']) {
					$key                        = key($categories);
					$categories[$key]['active'] = true;
					//$category['active'] = true;
					$categoryId = $category['parent_id'];

					if (! $categoryId) {
						break;
					}
				}
				$category = prev($categories);
			}
		}

		return $results;
	}
}
