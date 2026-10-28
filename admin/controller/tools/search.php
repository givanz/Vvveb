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

namespace Vvveb\Controller\Tools;

use function Vvveb\__;
use Vvveb\Controller\Base;
use function Vvveb\htmlToText;
use Vvveb\System\Extensions\Plugins;
use Vvveb\System\Images;
use function Vvveb\truncateWords;
use function Vvveb\url;

class Search extends Base {
	/**
	 * Recursively search the admin menu for matching items.
	 *
	 * @param string                                 $text    The search text to match.
	 * @param array<array-key, array<string, mixed>> $results Found results (passed by reference).
	 * @param array<int|string, mixed>               $menu    The menu array to search through.
	 *
	 * @return array<array-key, array<string, mixed>> The updated results array.
	 */
	private function searchMenu(string $text, array &$results, array $menu): array {
		foreach ($menu as $key => $value) {
			if (isset($value['heading'])) {
				continue;
			}

			if (isset($value['name']) && is_string($value['name']) && (stripos($value['name'], $text) !== false)) {
				$results[$value['name']] = [
					'icon'     => $value['icon'] ?? '',
					'icon-img' => $value['icon-img'] ?? '',
					'text'     => $value['name'],
					'url'      => $value['url'],
				];
			}

			if (isset($value['items']) && is_array($value['items'])) {
				$this->searchMenu($text, $results, $value['items']);
			}
		}

		return $results;
	}

	/**
	 * Perform a global search across products, posts, users, categories, menu, pages and plugins.
	 *
	 * @return void
	 */
	public function index(): void {
		$text   = $this->request->get['search'] ?? '';
		$type   = $this->request->get['type'] ?? '';

		$this->view->search = $text;
		$this->view->type   = $type;

		if (strlen($text) < 3) {
			$this->view->search = [];

			return;
		}

		$products   = new \Vvveb\Sql\ProductSQL();

		$options = [
			'limit' => 5,
			'like'  => $text,
		] + $this->global;

		unset($options['admin_id']);
		$results = $products->getAll($options);

		$search = [];

		if (isset($results['product'])) {
			$key = __('product');
			foreach ($results['product'] as $product) {
				$product['image'] = Images::image($product['image'], 'product', 'thumb');
				$url              = url(['module' => 'product/product', 'slug' => $product['slug'], 'product_id' => $product['product_id'], 'type' => $product['type']]);

				$search[$key][]  = [
					'src'         => $product['image'],
					'text'        => $product['name'],
					'description' => htmlToText(truncateWords($product['excerpt'] ?? $product['content'], 50)),
					'url'         => $url,
				];
			}
		}

		//if (count($search) < 5) {
		$posts   = new \Vvveb\Sql\PostSQL();
		$results = $posts->getAll($options);

		if (isset($results['post'])) {
			$key = __('post');
			foreach ($results['post'] as $post) {
				$post['image'] = Images::image($post['image'], 'post', 'thumb');
				$url           = url(['module' => 'content/post', 'slug' => $post['slug'], 'post_id' => $post['post_id'], 'type' => $post['type']]);

				$search[$key][] = [
					'src'         => $post['image'],
					'text'        => $post['name'],
					'description' => htmlToText(truncateWords($post['excerpt'] ?? $post['content'], 50)),
					'url'         => $url,
				];
			}
		}
		//}

		$users   = new \Vvveb\Sql\UserSQL();
		$results = $users->getAll($options + ['search' => $text]);

		if (isset($results['user'])) {
			$key = __('user');
			foreach ($results['user'] as $user) {
				$url           = url(['module' => 'user/user', 'user_id' => $user['user_id']]);
				if (isset($user['avatar'])) {
					$user['image'] = Images::image($user['avatar'], 'user');
				}

				$search[$key][] = [
					'src'         => $user['image'],
					'text'        => $user['first_name'] . ' ' . $user['last_name'],
					'description' => '(' . $user['username'] . ') ' . $user['email'],
					'url'         => $url,
				];
			}
		}

		$categories   = new \Vvveb\Sql\CategorySQL();
		$results      = $categories->getCategories($options + ['search' => $text]);

		if (isset($results['categories'])) {
			$key = __('taxonomy');
			foreach ($results['categories'] as $category) {
				$url           = url(['module' => 'content/categories', 'taxonomy_id' => $category['taxonomy_id']]);
				if (isset($category['avatar'])) {
					$category['image'] = Images::image($category['avatar'], 'category');
				}

				$search[$key][]  = [
					'src'         => $category['image'],
					'text'        => $category['name'],
					'description' => htmlToText(truncateWords($category['content'], 50)),
					'url'         => $url,
				];
			}
		}

		$menuResults = [];
		$this->searchMenu($text, $menuResults, $this->view->menu);

		if ($menuResults) {
			$search[__('Settings')] = $menuResults;
		}

		$pages  = [
			'index/index'             => __('Home'),
			'product/index'           => __('Shop'),
			'content'                 => __('Blog'),
			'user/index'              => __('User'),
			'user/login/index'        => __('Login'),
			'user/signup/index'       => __('Signup'),
			'cart/cart/index'         => __('Cart'),
			'checkout/checkout/index' => __('Checkout'),
		];

		$key = __('pages');
		foreach ($pages as $route => $name) {
			if (stripos($name, $text) !== false) {
				$url                = url($route);
				$search[$key][$url] = [
					'icon' => 'la la-external-link-alt',
					'text' => $name,
					'url'  => $url,
				];

				break;
			}
		}

		$plugins    = Plugins :: getList($this->global['site_id']);
		$pluginsUrl =  url(['module' => 'plugin/plugins']);
		$key        = __('plugins');
		foreach ($plugins as $plugin) {
			if (stripos($plugin['name'] ?? '', $text) !== false ||
				stripos($plugin['description'] ?? '', $text) !== false) {
				$search[$key][$plugin['slug']] = [
					'src'         => $plugin['thumb_url'],
					'text'        => $plugin['name'],
					'description' => $plugin['description'],
					'url'         => $plugin['settings'] ?? ($pluginsUrl . '#plugin-' . $plugin['slug'])
				];

				break;
			}
		}

		$this->view->search = $search;
	}
}
