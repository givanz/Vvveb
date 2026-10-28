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
	public static array $defaultOptions = [
		'start'                    => 0,
		'limit'                    => 7,
		'site_id'                  => null,
		'language_id'              => null,
		'order'                    => ['url', 'price asc'],
		'taxonomy_id'              => null,
		'post_id'                  => null,
		'parent_id'                => null,
		'search'                   => null,
		'type'                     => 'categories',
		'post_type'                => null,
		'count'                    => false,
		'parents_only'             => false,
		'parents_children_only'    => false,
		'parents_without_children' => false,
	];

	/**
	 * Disable caching when search is active.
	 *
	 * @return string|null Parent cache key or null to disable caching.
	 */
	public function cacheKey(): ?string {
		if (isset($this->options['search'])) {
			return null;
		}

		return parent::cacheKey();
	}

	/**
	 * Get categories with child counts, images, URLs, and pagination.
	 *
	 * @return array<string, mixed> Categories data with nested children counts and pagination info.
	 */
	public function results(): array {
		$category = new CategorySQL();

		$results       = $category->getCategories($this->options);
		$taxonomy_type = 'category';

		if (isset($this->options['type']) && $this->options['type'] === 'tags') {
			$taxonomy_type = 'tag';
		}

		//count the number of child categories (subcategories) for each category
		if (isset($results['categories'])) {
			foreach ($results['categories'] as $taxonomy_item_id => &$category) {
				$parent_id          = $category['parent_id'] ?? false;
				$category['active'] = false;

				if (!isset($category['children'])) {
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

		[$results] = Event :: trigger(__CLASS__, __FUNCTION__, $results);

		return $results;
	}

	/**
	 * Set active category based on current request module and category_id.
	 *
	 * @param array<string, mixed> &$results Component results to modify.
	 * @param int                  $index    Request index.
	 *
	 * @return array<string, mixed> Modified results with active flags.
	 */
	public function request(array &$results, int $index = 0): array {
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
					$categoryId                 = $category['parent_id'];

					if (!$categoryId) {
						break;
					}
				}
				$category = prev($categories);
			}
		}

		return $results;
	}
}
