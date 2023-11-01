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

namespace Vvveb\Controller\Editor;

use Vvveb\Controller\Base;
use Vvveb\Sql\categorySQL;
use Vvveb\System\Core\View;

class Autocomplete extends Base {
	private $themeConfig = [];

	function products() {
		$text             = $this->request->get['text'];

		$options = [
			'start'       => 0,
			'limit'       => 10,
			'search'      => '%' . $text . '%',
		] + $this->global;

		$products = new \Vvveb\Sql\ProductSQL();
		$results  = $products->getAll($options);

		foreach ($results['products'] as $product) {
			$search[$product['product_id']] = $product['name'];
		}

		$this->response->setType('json');
		$this->response->output($search);

		die();
	}

	function categories() {
		$text             = $this->request->get['text'];

		$options = [
			'start'       => 0,
			'limit'       => 10,
			'search'      => '%' . $text . '%',
		] + $this->global;

		$categories = new \Vvveb\Sql\CategorySQL();

		$results = $categories->getCategories($options);

		foreach ($results['categories'] as $category) {
			$search[$category['taxonomy_item_id']] = $category['name'];
		}

		$this->response->setType('json');
		$this->response->output($search);

		die();
	}

	function manufacturers() {
		$text             = $this->request->get['text'];

		$options = [
			'start'       => 0,
			'limit'       => 10,
			'search'      => '%' . $text . '%',
		] + $this->global;

		$manufacturers = new \Vvveb\Sql\ManufacturerSQL();

		$results = $manufacturers->getManufacturers($options);

		foreach ($results['manufacturers'] as $manufacturer) {
			$search[$manufacturer['manufacturer_id']] = $manufacturer['name'];
		}

		$this->response->setType('json');
		$this->response->output($search);

		die();
	}

	function posts() {
		$text             = $this->request->get['text'];

		$options = [
			'start'       => 0,
			'limit'       => 10,
			'search'      => '%' . $text . '%',
		] + $this->global;

		$posts = new \Vvveb\Sql\PostSQL();

		$results = $posts->getAll($options);

		foreach ($results['posts'] as $post) {
			$search[$post['post_id']] = substr($post['name'],0, 50);
		}

		$this->response->setType('json');
		$this->response->output($search);

		die();
	}

	function tags() {
		$taxonomy_id      = $this->request->get['taxonomy_id'];
		$taxonomy         = $this->request->get['taxonomy'];
		$text             = $this->request->get['text'];

		$taxonomy_itemSql = new categorySQL();

		$options = [
			'post_id'    		       => $post_id,
			'language_id'         => 1,
			'site_id'             => 1,
			'taxonomy'		          => $taxonomy,
			'start'               => 1,
			'limit'               => 100,
			'search'              => '%' . $text . '%',
		] + $this->global;

		$results = $taxonomy_itemSql->getCategories($options + ['taxonomy_id' => $taxonomy_id]);

		foreach ($results['categories'] as $id => &$taxonomy_item) {
			$search[$taxonomy_item['taxonomy_item_id']] = $taxonomy_item['name'];
		}

		$this->response->setType('json');
		$this->response->output($search);

		die();
	}
}
