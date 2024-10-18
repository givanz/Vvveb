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

use function Vvveb\getCurrentUrl;
use function Vvveb\sanitizeHTML;
use Vvveb\Sql\menuSQL;
use Vvveb\Sql\postSQL;
use Vvveb\Sql\productSQL;
use Vvveb\System\Component\ComponentBase;
use Vvveb\System\Event;
use function Vvveb\url;

class Menu extends ComponentBase {
	public static $defaultOptions = [
		'start'   => 0, //defaut, override from html
		'limit'   => 10000,
		'menu_id' => null, //unset, set from html
		'slug'    => null, //unset, set from html
	];

	function results() {
		$options = $this->options;

		//if menu id is set then ignore slug
		if (isset($options['menu_id'])) {
			unset($options['slug']);
		}

		$menuSql               = new menuSQL();
		$results               = $menuSql->get($options);

		//count the number of child menus (subcategories) for each category
		if (isset($results['menu'])) {
			$productIds = [];
			$postIds    = [];

			foreach ($results['menu'] as $taxonomy_item_id => &$category) {
				$parent_id = $category['parent_id'] ?? false;
				$type      = $category['type'] ?? 'link';

				if (! isset($category['children'])) {
					$category['children'] = 0;
				}

				if ($parent_id > 0) {
					$parent = &$results['menu'][$parent_id];

					if (isset($parent['children'])) {
						$parent['children']++;
					} else {
						$parent['children'] = 1;
					}

					if ($type == 'text') {
						$parent['has-text'] = true;
					}
				}

				if ($type == 'product') {
					$productIds[$taxonomy_item_id]            = $category['item_id'];
					$taxonomyProducts[$category['item_id']][] = $taxonomy_item_id;
				}

				if (($type == 'post' || $type == 'page') && $category['item_id']) {
					$postIds[$category['item_id']]         = $category['item_id'];
					$taxonomyPosts[$category['item_id']][] = $taxonomy_item_id;
				}
			}

			//get product items
			if ($productIds) {
				$productOptions = [
					'product_id'  => $productIds,
					'language_id' => $options['language_id'],
					'site_id'     => $options['site_id'],
				];

				$productSql = new productSql();
				$products   = $productSql->getAll($productOptions);

				if (isset($products['products']) && $products['products']) {
					foreach ($products['products'] as $product) {
						foreach ($taxonomyPosts[$product['product_id']] as $taxonomy_item_id) {
							$taxonomy_item_id   = $productTaxonomy[$product['product_id']];
							$category           = &$results['menu'][$taxonomy_item_id];
							$route              = "product/{$category['type']}/index";
							$category['url']    = url($route, ['slug'=> $product['slug'], 'product_id'=> $product['product_id']]);
							$category['name']   = $product['name'];
						}
					}
				}
			}

			//get post items
			if ($postIds) {
				$postOptions = [
					'post_id'     => $postIds,
					'language_id' => $options['language_id'],
					'site_id'     => $options['site_id'],
				];

				$postSql = new postSql();
				$posts   = $postSql->getAll($postOptions);

				if (isset($posts['post']) && $posts['post']) {
					foreach ($posts['post'] as $post) {
						foreach ($taxonomyPosts[$post['post_id']] as $taxonomy_item_id) {
							$category         = &$results['menu'][$taxonomy_item_id];
							$route            = "content/{$category['type']}/index";
							$url              = url($route, ['slug'=> $post['slug'], 'post_id'=> $post['post_id']]);
							$category['url']  = $url;
							$category['name'] = $post['name'];
						}
					}
				}
			}
		}

		list($results) = Event :: trigger(__CLASS__,__FUNCTION__, $results);

		return $results;
	}

	//called on each request
	function request(&$results, $index = 0) {
		$currentUrl            = getCurrentUrl();

		if (isset($results['menu'])) {
			foreach ($results['menu'] as $taxonomy_item_id => &$category) {
				$category['active'] = isset($category['url']) && ($category['url'] === $currentUrl);
			}
		}

		return $results;
	}

	//called by editor on page save for each component on page
	//this method is called from admin app
	static function editorSave($id, $fields, $type = 'menu') {
		$menu              = new menuSQL();
		$menu_item_content = [];

		foreach ($fields as $field) {
			$name  = $field['name'];
			$value = $field['value'];

			if ($name == 'name') {
				$menu_item_content[$name] = strip_tags($value);
			} else {
				if ($name == 'content') {
					$menu_item_content[$name] = sanitizeHTML($value);
				} else {
					$menu_item[$name] = $value;
				}
			}
		}
		//$menu_item['menu_item_content']['post_id'] = $id;
		$menu_item_content['language_id']      = 1;
		$menu_item_content['content']          = $menu_item_content['content'] ?? '';
		$menu_item['menu_item_content'][]      = $menu_item_content;
		$menu_item['menu_item_id']             = $id;

		if ((isset($menu_item_content['name']) && $menu_item_content['name']) ||
			(isset($menu_item_content['content']) && $menu_item_content['content'])) {
			$result = $menu->editMenuItem(['menu_item' => $menu_item, 'menu_item_id' => $id]);
		}
	}
}
