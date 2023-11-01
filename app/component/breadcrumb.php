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

namespace Vvveb\Component;

use function Vvveb\__;
use Vvveb\Sql\CategorySQL;
use Vvveb\System\Component\ComponentBase;
use Vvveb\System\Core\Request;
use Vvveb\System\Event;
use function Vvveb\url;

class Breadcrumb extends ComponentBase {
	public static $defaultOptions = [
	];

	public $options = [];

	function cacheKey() {
		//disable caching
		return false;
	}

	function results() {
		$request = Request::getInstance();
		$route   = $request->get['route'] ?? '';
		$slug    = $request->get['slug'] ?? '';
		$name    = $request->get['name'] ?? '';

		$breadcrumb = [
			['text' => 'home', 'url' => '/'],
		];

		switch ($route) {
			//product page
			case 'product/product/index':
				$breadcrumb = [
					['text' => 'home', 'url' => '/'],
				];

				$product_id = $request->get['product_id'] ?? false;

				if ($product_id) {
					$category = new CategorySQL();
					$result   = $category->getCategory(
						['product_id' => $product_id, 'limit' => 1, 'type' => 'categories', 'post_type' => 'product']
						+ self :: $global);

					if ($result) {
						$breadcrumb = array_merge($breadcrumb, [
							['text' => $result['name'], 'url' => url('product/category/index', $result)],
							['text' => $slug, 'url' => false],
						]);
					}
				}

			break;
			//product category page
			case 'product/category/index':
				$breadcrumb = [
					['text' => 'home', 'url' => '/'],
					['text' => $slug, 'url' => false],
				];

			break;
			//shop page
			case 'product/index':
				$breadcrumb = [
					['text' => 'home', 'url' => '/'],
					['text' => 'shop', 'url' => false],
				];

			break;
			//manufacturer page
			case 'product/manufacturer/index':
				$breadcrumb = [
					['text' => 'home', 'url' => '/'],
					['text' => $slug, 'url' => false],
				];

			break;
			//vendor page
			case 'product/vendor/index':
				$breadcrumb = [
					['text' => 'home', 'url' => '/'],
					['text' => $slug, 'url' => false],
				];

			break;
			//blog page
			case 'content':
			case 'content/index':
				$breadcrumb = [
					['text' => 'home', 'url' => '/'],
					['text' => __('blog'), 'url' => false],
				];

			break;
			//post page
			case 'content/post/index':
				$post_id = $request->get['post_id'] ?? false;

				if ($post_id) {
					$category = new CategorySQL();
					$result   = $category->getCategory(
						['post_id' => $post_id, 'limit' => 1, 'type' => 'categories', 'post_type' => 'post']
						+ self :: $global);
				}

				$breadcrumb = [
					['text' => 'home', 'url' => '/'],
					['text' => $result['name'], 'url' => url('content/category/index', $result)],
					['text' => $slug, 'url' => false],
				];

			break;

			case 'content/page/index':
				$post_id = $request->get['post_id'] ?? false;

				if ($post_id) {
					$category = new CategorySQL();
					$result   = $category->getCategory(
						['post_id' => $post_id, 'limit' => 1, 'type' => 'categories', 'post_type' => 'post']
						+ self :: $global);
				}

				$breadcrumb = [
					['text' => 'home', 'url' => '/'],
					['text' => $slug, 'url' => false],
				];

			break;
			//post category page
			case 'content/category/index':
				$breadcrumb = [
					['text' => 'home', 'url' => '/'],
					['text' => $slug, 'url' => false],
				];

			break;

			default:
		}

		$results = [
			'breadcrumb' => $breadcrumb,
		];

		list($results) = Event :: trigger(__CLASS__,__FUNCTION__, $results);

		return $results;
	}
}
