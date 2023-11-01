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

use \Vvveb\Sql\categorySQL;
use function Vvveb\__;
use Vvveb\Controller\Base;

class Categories extends Base {
	function delete() {
		$taxonomy_item_id = $this->request->post['taxonomy_item_id'] ?? false;
		$categories       = new categorySQL();

		//['taxonomy_items' => $data]
		if ($taxonomy_item_id && $categories->deleteTaxonomyItem(['taxonomy_item_id' => $taxonomy_item_id])) {
			echo __('Item removed!');
		}

		die();
	}

	function reorder() {
		$data       = $this->request->post;
		$categories = new categorySQL();

		//['taxonomy_items' => $data]
		if ($categories->updateTaxonomyItems($data)) {
			echo __('Items reordered!');
		}

		die();
	}

	function add() {
		$data = $this->request->post;

		$categories  = new categorySQL();

		$options = [
			'taxonomy_item' => $data,
		] + $this->global;

		if (isset($data['taxonomy_item_id']) && $data['taxonomy_item_id']) {
			$options['taxonomy_item_id'] = $data['taxonomy_item_id'];
			$results                     = $categories->editTaxonomyItem($options);

			if ($results) {
				echo __('Item edited!');
			}
		} else {
			$results = $categories->addTaxonomyItem($options);

			if ($results) {
				echo __('Item added!');
			}
		}

		die();

		return;
	}

	function index() {
		$view        = $this->view;
		$categories  = new categorySQL();

		$page        = $this->request->get['page'] ?? 1;
		$type        = $this->request->get['type'] ?? 1;
		$limit       = 1000;
		$taxonomy_id = 1;

		$options = [
			'start'       => ($page - 1) * $limit,
			'limit'       => $limit,
			//'taxonomy_id' => $taxonomy_id,
			'post_type'   => $type,
			'type'        => 'categories',
		] + $this->global;

		$view->taxonomy_id = $taxonomy_id;

		$results = $categories->getCategoriesAllLanguages($options);

		foreach ($results['categories'] as &$taxonomy_item) {
			$langs                      = $taxonomy_item['languages'] ? json_decode($taxonomy_item['languages'], true) : [];
			$taxonomy_item['languages'] = [];

			if ($langs) {
				foreach ($langs as $lang) {
					$taxonomy_item['languages'][$lang['language_id']] = $lang;
				}

				$taxonomy_item['name'] = $taxonomy_item['languages'][$this->global['language_id']]['name'] ?? $langs[0]['name'] ?? '';
			}
		}

		$view->set($results);
	}
}
