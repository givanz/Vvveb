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
		'absoluteURL' => null,
	];

	public $options = [];

	function cacheKey() {
		//disable caching
		return false;
	}

	function results() {
		$request     = Request::getInstance();
		$module      = $request->get['module'] ?? '';
		$slug        = $request->get['slug'] ?? '';
		$name        = $request->get['name'] ?? '';
		$homeText    = __('Home');
		$shopText    = __('Shop');
		$blogText    = __('Blog');
		$urlOptions  = [];

		if ($this->options['absoluteURL']) {
			$urlOptions += ['host' => SITE_URL, 'scheme' => $_SERVER['REQUEST_SCHEME'] ?? 'http'];
		}

		if ($this->options['default_language'] != $this->options['language']) {
			$urlOptions += ['language'=> $this->options['language']];
		}

		$breadcrumb = [
			['text' => $homeText, 'url' => url('index/index', $urlOptions)],
		];

		switch ($module) {
			//product page
			case 'product/product/index':
				$product_id = $request->get['product_id'] ?? false;

				$breadcrumb[] = ['text' => $shopText, 'url' => url('product/index', $urlOptions)];

				if ($product_id) {
					$category = new CategorySQL();
					$result   = $category->getCategory(
						['product_id' => $product_id, 'limit' => 1, 'type' => 'categories', 'post_type' => 'product']
						+ self :: $global);

					if ($result) {
						$breadcrumb[] = ['text' => $result['name'], 'url' => url('product/category/index', $result + $urlOptions)];
					}
				}

				$breadcrumb[] = ['text' => $slug, 'url' => false];

			break;
			//product category page
			case 'product/category/index':
				$breadcrumb[] = ['text' => $shopText, 'url' => url('product/index', $urlOptions)];
				$breadcrumb[] = ['text' => $slug, 'url' => false];

			break;
			//shop page
			case 'product/index':
				$breadcrumb[] = ['text' => $shopText, 'url' => false];

			break;
			//manufacturer page
			case 'product/manufacturer/index':
				$breadcrumb[] = ['text' => $shopText, 'url' => url('product/index', $urlOptions)];
				$breadcrumb[] = ['text' => $slug, 'url' => false];

			break;
			//vendor page
			case 'product/vendor/index':
				$breadcrumb[] = ['text' => $shopText, 'url' => url('product/index', $urlOptions)];
				$breadcrumb[] = ['text' => $slug, 'url' => false];

			break;
			//blog page
			case 'content':
			case 'content/index':
				$breadcrumb[] = ['text' => $blogText, 'url' => false];

			break;
			//post page
			case 'content/post/index':
				$post_id      = $request->get['post_id'] ?? false;
				$breadcrumb[] =  ['text' => $blogText, 'url' => url('content', $urlOptions)];

				if ($post_id) {
					$category = new CategorySQL();
					$result   = $category->getCategory(
						['post_id' => $post_id, 'limit' => 1, 'type' => 'categories', 'post_type' => 'post']
						+ self :: $global);

					if ($result && isset($result['category'])) {
						$breadcrumb[] = ['text' => $result['name'], 'url' => url('content/category/index', $result + $urlOptions)];
					}
				}

				$breadcrumb[] = ['text' => $slug, 'url' => false];

			break;

			case 'content/page/index':
				$post_id = $request->get['post_id'] ?? false;

				$breadcrumb[] = ['text' => $slug, 'url' => false];

			break;
			//post category page
			case 'content/category/index':
				$breadcrumb[] = ['text' => $slug, 'url' => false];

			break;
			//compare
			case 'cart/cart/index':
				$breadcrumb[] = ['text' => __('Cart'), 'url' => false];

			break;
			//compare
			case 'cart/compare/index':
				$breadcrumb[] = ['text' => __('Compare'), 'url' => false];

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
