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
use Vvveb\Sql\categorySQL;

class Tags extends Base {
	function index() {
		$view        = $this->view;
		$categories  = new categorySQL();

		$page    = $this->request->get['page'] ?? 1;
		$limit   = 1000;

		$options = [
			'start'        => ($page - 1) * $limit,
			'limit'        => $limit,
			'type'         => 'tags',
		] + $this->global;

		$results = $categories->getCategoriesAllLanguages($options);

		if (isset($results['categories'])) {
			foreach ($results['categories'] as &$taxonomy_item) {
				/*
				$taxonomy_item['languages'] = json_decode($taxonomy_item['languages'], true);
				$taxonomy_item['name']      = $taxonomy_item['languages'][0]['name'] ?? '';
				*/
				if (isset($taxonomy_item['languages'])) {
					$langs                      = json_decode($taxonomy_item['languages'], true);
					$taxonomy_item['languages'] = [];

					if ($langs) {
						foreach ($langs as $lang) {
							$taxonomy_item['languages'][$lang['language_id']] = $lang;
						}

						$taxonomy_item['name'] = $taxonomy_item['languages'][$this->global['language_id']]['name'] ?? $langs[0]['name'] ?? '';
					}
				}

				$taxonomy_item['edit-url']   = \Vvveb\url(['module' => 'content/tag', 'taxonomy_item_id' => $taxonomy_item['taxonomy_item_id']]);
			}
		}

		$view->set($results);
	}
}
