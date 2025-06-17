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

use function Vvveb\__;
use Vvveb\Controller\Base;
use Vvveb\Sql\categorySQL;
use Vvveb\System\Images;

class Tags extends Base {
	function delete() {
		$taxonomy_item_id = $this->request->post['taxonomy_item_id'] ?? false;
		$categories       = new categorySQL();

		if (is_numeric($taxonomy_item_id)) {
			$taxonomy_item_id = [$taxonomy_item_id];
		}

		if ($taxonomy_item_id && $categories->deleteTaxonomyItem(['taxonomy_item_id' => $taxonomy_item_id])) {
			$this->view->success[] = 'Item removed!';
		} else {
			$this->view->errors[] = __('Error saving!');
		}

		return $this->index();
	}

	function index() {
		$view        = $this->view;
		$categories  = new categorySQL();

		$page        = $this->request->get['page'] ?? 1;
		$type        = $this->request->get['type'] ?? '';
		$taxonomy_id = $this->request->get['taxonomy_id'] ?? false;
		$limit       = 1000;

		$options = [
			'start'       => ($page - 1) * $limit,
			'limit'       => $limit,
			'post_type'   => $type,
			'taxonomy_id' => $taxonomy_id,
			'type'        => 'tags',
		] + $this->global;

		$results = $categories->getCategoriesAllLanguages($options);

		if (isset($results['categories'])) {
			foreach ($results['categories'] as &$taxonomy_item) {
				/*
				$taxonomy_item['languages'] = json_decode($taxonomy_item['languages'], true);
				$taxonomy_item['name']      = $taxonomy_item['languages'][0]['name'] ?? '';
				*/
				$taxonomy_item['image_url'] = Images::image($taxonomy_item['image'], 'tag', 'thumb');

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

				$url                           = ['module' => 'content/tag', 'taxonomy_item_id' => $taxonomy_item['taxonomy_item_id'], 'taxonomy_id' => $taxonomy_id, 'type' => $type];
				$taxonomy_item['edit-url']     = \Vvveb\url($url);
				$taxonomy_item['delete-url']   = \Vvveb\url(['module' => 'content/tags', 'action' => 'delete'] + $url);
			}
		}

		$view->add_url    = \Vvveb\url(['module' => 'content/tag', 'taxonomy_id' => $taxonomy_id, 'type' => $type]);
		$view->set($results);
	}
}
