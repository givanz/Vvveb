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
use Vvveb\Sql\CategorySQL;
use Vvveb\System\Images;

class Category extends Base {
	protected $type = 'category';

	function index() {
		$slug                      = $this->request->get['slug'] ?? '';
		$type                      = $this->request->get['type'] ?? 'post';
		$this->view->category_name = $slug;

		if ($slug) {
			$categorySql = new CategorySQL();
			$options     = $this->global + ['slug' => $slug/*, 'post_type' => $type*/];
			$category    = $categorySql->getCategoryBySlug($options);

			if ($category) {
				$this->request->get['category_id'] = $this->request->request['taxonomy_item_id'] = $category['taxonomy_item_id'];
				$this->request->get['name']        = $category['name'];

				if (isset($category['image']) && $category['image']) {
					$category['image_url'] = Images::image($category['image'], 'product', 'medium');
				}

				$category['title'] = $category['name'];
				if (isset($this->global['site']['description']['title'])) {
					$category['title'] = $category['title'] . ' - ' . $this->global['site']['description']['title'];
				}

				$this->request->get['taxonomy_item_id'] = $category['taxonomy_item_id'];
				$this->request->get['slug']             = $category['slug'];
				$this->view->category                   = $category;
			} else {
				$message = sprintf(__('%s not found!'), ucfirst(__($this->type)));
				$this->notFound(true, ['message' => $message, 'title' => $message]);
			}
		}
	}
}
