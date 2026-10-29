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

use Vvveb\Sql\CategorySQL;
use Vvveb\Sql\TaxonomySQL;

trait TaxonomiesTrait {
	/**
	 * Retrieves all taxonomies and their items for a given post.
	 *
	 * @param int|false $post_id The post ID to load taxonomy assignments for, or false.
	 *
	 * @return array<int, array<string, mixed>> Array of taxonomy records with nested items.
	 */
	protected function taxonomies(int|false $post_id = false): array {
		//get taxonomies for post type
		$taxonomies = new TaxonomySQL();
		$results    = $taxonomies->getAll(
			['post_type' => $this->type]
		);

		//get taxonomies content
		if ($results) {
			$taxonomy_itemSQL = new CategorySQL();

			$options =  [
				'post_type' => $this->type,
				'start'     => 0,
				'limit'     => 1000,
			] + $this->global;

			if ($post_id) {
				$options["{$this->object}_id"] = $post_id;
			}

			if (isset($results['taxonomy'])) {
				foreach ($results['taxonomy'] as $id => &$taxonomy_item) {
					$taxonomy_item['taxonomy_item'] = [];
					//for tags don't retrive taxonomies if no post id provided
					if ($taxonomy_item['type'] != 'tags' || $post_id) {
						$options                        = ['taxonomy_id' => $id, 'type' => $taxonomy_item['type']] + $options;
						$taxonomies                     = $taxonomy_itemSQL->getCategories($options);
						if (isset($taxonomies['categories'])) {
							foreach ($taxonomies['categories'] as &$taxonomy) {
								$taxonomy = ['taxonomy_item_id' => $taxonomy['taxonomy_item_id'], 'taxonomy_id' => $taxonomy['taxonomy_id'], 'name' => $taxonomy['name'], 'parent_id' => $taxonomy['name'], 'checked' => $taxonomy['checked'] ?? false, 'slug' => $taxonomy['slug'], 'language_id' => $taxonomy['language_id'], 'item_id' => $taxonomy['item_id'], 'image' => $taxonomy['image']];
							}
						}

						$taxonomy_item['taxonomy_item'] = $taxonomies;
					}
				}
			}
		}

		return $results['taxonomy'] ?? [];
	}
}
