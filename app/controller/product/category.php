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

namespace Vvveb\Controller\Product;

use \Vvveb\Sql\CategorySQL;
use function Vvveb\__;
use Vvveb\Controller\Base;

class Category extends Base {
	function index() {
		$slug                      = $this->request->get['slug'] ?? '';
		$this->view->category_name = $slug;

		if ($slug) {
			$categorySql = new CategorySQL();
			$options     = $this->global + ['slug' => $slug, 'post_type' => 'product'];
			$category    = $categorySql->getCategoryBySlug($options);

			if ($category) {
				$this->request->request['category_id'] = $this->request->request['taxonomy_item_id'] = $category['taxonomy_item_id'];
				$this->view->category                  = $category;
				$this->view->category_name             = $category['name'];
			} else {
				$message = __('Category not found!');
				$this->notFound(true, $message);
			}
		}
	}
}
