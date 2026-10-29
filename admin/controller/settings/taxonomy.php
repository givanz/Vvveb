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
use Vvveb\System\Event;

class Taxonomy extends Crud {
	/** @var string The entity type identifier */
	protected string $type = 'taxonomy';

	/** @var string The controller route slug */
	protected string $controller = 'taxonomy';

	/** @var string The module namespace */
	protected string $module = 'settings';

	/**
	 * Display the taxonomy edit/create form with post type options.
	 *
	 * @return void
	 */
	public function index(): void {
		parent::index();

		$this->view->type          = ['categories' => __('Categories'), 'tags' => __('Tags')];
		$postType                  = ['post' => __('Post'), 'page' => __('Page'), 'product' => __('Product')];

		$userPostTypes         = \Vvveb\getSetting('post', 'types', []);
		[$pluginPostTypes]     = Event::trigger('Vvveb\Controller\Base', 'customPost', []);
		$customPost            = $userPostTypes + $pluginPostTypes;
		array_walk($customPost, fn (&$type, $key) => $type = ucfirst($key));
		$postType += $customPost;

		$userProductTypes         = \Vvveb\getSetting('product', 'types', []);
		[$pluginProductTypes]     = Event::trigger('Vvveb\Controller\Base', 'customProduct', []);
		$customProduct            = $userProductTypes + $pluginProductTypes;
		array_walk($customProduct, fn (&$type, $key) => $type = ucfirst($key));
		$postType += $customProduct;

		$this->view->post_type     = $postType;
	}
}
