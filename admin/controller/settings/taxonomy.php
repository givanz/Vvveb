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

namespace Vvveb\Controller\Settings;

use function Vvveb\__;
use Vvveb\Controller\Crud;

class Taxonomy extends Crud {
	protected $type = 'taxonomy';

	protected $controller = 'taxonomy';

	protected $module = 'settings';

	function index() {
		parent::index();

		$this->view->type          = ['categories' => __('Categories'), 'tags' => __('Tags')];
		$this->view->post_type     = ['post' => __('Post'), 'page' => __('Page'), 'product' => __('product')];
		//$this->view->taxonomy_id = $taxonomy_item_id;
	}
}
