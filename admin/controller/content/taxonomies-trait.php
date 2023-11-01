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

use Vvveb\Sql\categorySQL;
use Vvveb\Sql\taxonomySQL;

trait TaxonomiesTrait {
	protected function taxonomies($post_id = false) {
		//get taxonomies for post type
		$taxonomies = new taxonomySQL();
		$results    = $taxonomies->getAll(
			['post_type'    => $this->type]
		);

		//get taxonomies content
		if ($results) {
			$taxonomy_itemSql = new categorySQL();

			$options =  [
				'post_type'  => $this->type,
				'start'      => 0,
				'limit'      => 1000,
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
						$taxonomy_item['taxonomy_item'] = $taxonomy_itemSql->getCategories($options);
					}
				}
			}
		}

		return $results['taxonomy'] ?? [];
	}
}
