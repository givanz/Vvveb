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

use Vvveb\Sql\CategorySQL;
use Vvveb\System\Component\ComponentBase;
use Vvveb\System\Event;
use Vvveb\System\Images;
use function Vvveb\url;

class Categories extends ComponentBase {
	public static $defaultOptions = [
		'start'                    => 0,
		'limit'                    => 7,
		'site_id'                  => NULL,
		'language_id'              => NULL,
		'order'                    => ['url', 'price asc'],
		'taxonomy_id'              => NULL,
		'post_id'                  => NULL,
		'parent_id'                => NULL,
		'search'                   => NULL,
		'type'                     => 'categories',
		'post_type'                => NULL,
		'count'             	   => false, //include number of posts
		'parents_only'             => false,
		'parents_children_only'    => false,
		'parents_without_children' => false,
	];

	function cacheKey() {
		if (isset($this->options['search'])) {
			return false;
		}

		return parent::cacheKey();
	}

	function results() {
		$category = new CategorySQL();

		$results  = $category->getCategories($this->options);
		$taxonomy_type = 'category';

		if (isset($this->options['type']) && $this->options['type'] == 'tags') {
			$taxonomy_type = 'tag';
		}

		//count the number of child categories (subcategories) for each category
		if (isset($results['categories'])) {
			foreach ($results['categories'] as $taxonomy_item_id => &$category) {
				$parent_id          = $category['parent_id'] ?? false;
				$category['active'] = false;

				if (! isset($category['children'])) {
					$category['children'] = 0;
				}

				if (isset($this->options['post_type']) && $this->options['post_type']) {
					$category['post_type'] = $this->options['post_type'];
				}

				$url = ['slug' => $category['slug']];
				if ($category['post_type'] != 'post') {
					$url['type'] = $category['post_type'];
				}

				$category['url'] = url('content/' . $taxonomy_type . '/index', $url);

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

		$results['limit']  = $this->options['limit'];
		$results['start']  = $this->options['start'];
		$results['search'] = $this->options['search'] ?? '';

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
