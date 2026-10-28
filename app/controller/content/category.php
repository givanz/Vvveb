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
use Vvveb\System\Event;
use Vvveb\System\Images;

class Category extends Base {
	/** @var string Taxonomy type name. */
	protected string $type = 'category';

	/** @var string Object type handled by this category. */
	protected string $object = 'post';

	/** @var string Route namespace. */
	protected string $namespace = 'content';

	/** @var string Fully-qualified class name for event dispatching. */
	protected string $class = __CLASS__;

	/**
	 * Display posts filtered by category slug.
	 *
	 * @return void
	 */
	public function index(): void {
		$slug                      = $this->request->get['slug'] ?? '';
		$type                      = $this->request->get['type'] ?? $this->object;
		$this->view->category_name = $slug;

		if ($slug) {
			$categorySQL = new CategorySQL();
			$options     = $this->global + ['slug' => $slug];
			if ($type) {
				$options['post_type'] = $type;
			}

			$category    = $categorySQL->getCategoryBySlug($options);

			if ($category) {
				$this->request->get['category_id'] = $this->request->request['taxonomy_item_id'] = $category['taxonomy_item_id'];
				$this->request->get['name']        = $category['name'];

				if (isset($category['image']) && $category['image']) {
					$size                  = [$this->global['site']['category_width'] ?? 150, $this->global['site']['category_height'] ?? 150];
					$category['image_url'] = Images::image($category['image'], 'category', $size, $this->global['site']['category_method'] ?? 'cs');
				}

				$category['title'] = $category['name'];
				if (isset($this->global['site']['description']['title'])) {
					$category['title'] = $category['title'] . ' - ' . $this->global['site']['description']['title'];
				}

				$this->request->get['taxonomy_item_id'] = $category['taxonomy_item_id'];
				$this->request->get['slug']             = $category['slug'];

				[$category, $type]    = Event :: trigger($this->class, __FUNCTION__ . ':after', $category, $type);
				$this->view->category = $category;

				if ($type != $this->object) {
					//content/category-page.html
					$template = $this->namespace . '/' . $this->type . '-' . $type . '.html';
					$this->view->template($template);
					$this->view->tplFile($this->namespace . '/' . $this->type . '.tpl');
				}
			} else {
				$message = sprintf(__('%s not found!'), ucfirst(__($this->type)));
				$this->notFound(['message' => $message, 'title' => $message]);
			}
		}
	}
}
