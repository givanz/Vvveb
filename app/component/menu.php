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
use function Vvveb\getCurrentUrl;
use function Vvveb\sanitizeHTML;
use Vvveb\Sql\MenuSQL;
use Vvveb\Sql\PostSQL;
use Vvveb\Sql\ProductSQL;
use Vvveb\System\Component\ComponentBase;
use Vvveb\System\Event;
use Vvveb\System\Images;
use Vvveb\System\Sites;
use function Vvveb\url;

class Menu extends ComponentBase {
	public static array $defaultOptions = [
		'start'       => 0,
		'limit'       => 10000,
		'menu_id'     => null,
		'slug'        => null,
		'site_id'     => null,
		'language_id' => null,
	];

	/**
	 * Get menu items with product/post URLs, images, and hierarchy.
	 *
	 * @return array<string, mixed> Menu data with menu_item list and count.
	 */
	public function results(): array {
		$options = $this->options;

		//if menu id is set then ignore slug
		if (isset($options['menu_id'])) {
			unset($options['slug']);
		}

		$menuSQL               = new MenuSQL();
		$results               = $menuSQL->getMenuItems($options);
		$noTranslationText     = '[' . __('No translation') . ']';

		$defaultLanguage = true;
		$language        = ['language' => $options['language']];

		if (self :: $global['default_language'] != self :: $global['language']) {
			$defaultLanguage = false;
		} else {
			if (!self :: $global['default_lang_slug']) {
				$language  = [];
			}
		}

		//count the number of child menus (subcategories) for each category
		if (isset($results['menu_item'])) {
			$productIds       = [];
			$postIds          = [];
			$taxonomyProducts = [];
			$taxonomyPosts    = [];

			foreach ($results['menu_item'] as $taxonomy_item_id => &$category) {
				$parent_id = $category['parent_id'] ?? false;
				$type      = $category['type'] ?? 'link';

				if (!isset($category['children'])) {
					$category['children'] = 0;
				}

				if (isset($category['options']) && $category['options']) {
					$category['options'] = json_decode($category['options'], true);
				} else {
					$category['options'] = [];
				}

				$category['img'] = null;
				if (isset($category['options']['img'])) {
					$category['img'] = Images::image($category['options']['img'], 'menu_item');
				}

				if ($parent_id > 0) {
					$parent = &$results['menu_item'][$parent_id];

					if (isset($parent['children'])) {
						$parent['children']++;
					} else {
						$parent['children'] = 1;
					}

					if ($type === 'text') {
						$parent['has-text']    = true;
						$category['content']   = $category['content'] ?? (!$defaultLanguage ? $noTranslationText : null);
					}
				}

				if ($type === 'product') {
					$productIds[$taxonomy_item_id]            = $category['item_id'];
					$taxonomyProducts[$category['item_id']][] = $taxonomy_item_id;
				}

				if (($type === 'post' || $type === 'page') && $category['item_id']) {
					$postIds[$category['item_id']]         = $category['item_id'];
					$taxonomyPosts[$category['item_id']][] = $taxonomy_item_id;
				}

				if ($type === 'home') {
					$category['url']  = url('index/index', $language);
					$category['name'] = $category['name'] ?: __('Home');
				}

				if ($type === 'blog') {
					$category['url']  = url('content', $language);
					$category['name'] = $category['name'] ?: __('Blog');
				}

				if ($type === 'shop') {
					$category['url']  = url('product/index', $language);
					$category['name'] = $category['name'] ?: __('Shop');
				}

				if (($type === 'link' && isset($category['url'])) && (strncmp($category['url'], 'http', 4) !== 0)) {
					$category['url'] = V_SUBDIR_INSTALL . Sites::url($category['url'] ?? '');
				}

				$category['name']          = $category['name'] ?? (!$defaultLanguage ? $noTranslationText : null);
				$category['language_id'] ??= $options['language_id'];
			}

			//get product items
			if ($productIds) {
				$productOptions = [
					'product_id'  => array_values($productIds),
					'language_id' => $options['language_id'],
				];

				$productSQL = new ProductSQL();
				$products   = $productSQL->getAll($productOptions);

				if (!empty($products['product'])) {
					foreach ($products['product'] as $product) {
						//map back from product id to the menu items referencing it
						foreach ($taxonomyProducts[$product['product_id']] ?? [] as $taxonomy_item_id) {
							if (!isset($results['menu_item'][$taxonomy_item_id])) {
								continue;
							}

							$category = &$results['menu_item'][$taxonomy_item_id];
							$route    = "product/{$category['type']}/index";

							$url      = $language;

							if (($product['type'] ?? 'product') != 'product') {
								$url['type'] = $product['type'];
							}

							//if translation is missing slug is not available
							if (isset($product['slug']) && $product['slug']) {
								$url['slug'] = $product['slug'];
							} else {
								$url['product_id'] = $product['product_id'];
							}

							$category['url']           = url($route, $url);
							$category['name']          = $product['name'] ?? (!$defaultLanguage ? $noTranslationText : null);
							$category['language_id']   = $product['language_id'] ?: ($options['language_id'] ?? null);
						}
					}
				}
			}

			//get post items
			if ($postIds) {
				$postOptions = [
					'post_id'     => $postIds,
					'language_id' => $options['language_id'],
				];

				$postSQL = new PostSQL();
				$posts   = $postSQL->getAll($postOptions);

				if (isset($posts['post']) && $posts['post']) {
					foreach ($posts['post'] as $post) {
						foreach ($taxonomyPosts[$post['post_id']] as $taxonomy_item_id) {
							$category = &$results['menu_item'][$taxonomy_item_id];
							$route    = "content/{$category['type']}/index";
							$url      = $post['slug'] ? url($route, ['slug' => $post['slug'], 'post_id' => $post['post_id']] + $language) : '/';

							//url
							$url                  = $language;

							if ($post['type'] != 'post' && $post['type'] != 'page') {
								$url['type'] = $post['type'];
								$type        = 'post';
							}

							//if translation is missing slug is not available
							if (isset($post['slug']) && $post['slug']) {
								$url['slug'] = $post['slug'];
							} else {
								$url['post_id'] = $post['post_id'];
							}

							$category['url']           = url($route, $url);
							$category['name']          = $post['name'] ?? (!$defaultLanguage ? $noTranslationText : null);
							$category['language_id']   = $post['language_id'] ?? $options['language_id'];
						}
					}
				}
			}
		} else {
			$results = ['menu_item' => [], 'count' => 0];
		}

		[$results] = Event :: trigger(__CLASS__, __FUNCTION__, $results);

		return $results;
	}

	/**
	 * Set active menu item based on current URL match.
	 *
	 * @param array<string, mixed> $results Component results to modify.
	 * @param int                  $index   Request index.
	 *
	 * @return array<string, mixed> Modified results with active flags.
	 */
	public function request(array &$results, int $index = 0): array {
		if (isset($results['menu_item'])) {
			$currentUrl            = getCurrentUrl();

			foreach ($results['menu_item'] as $taxonomy_item_id => &$category) {
				$category['active'] = isset($category['url']) && ($category['url'] === $currentUrl);
			}
		}

		return $results;
	}

	/**
	 * Save menu item content from editor on page save.
	 *
	 * @param int                              $id     Menu item ID.
	 * @param array<int, array<string, mixed>> $fields Field data from editor.
	 * @param string                           $type   Menu type identifier.
	 *
	 * @return void
	 */
	public static function editorSave(int $id, array $fields, string $type = 'menu'): void {
		$menu              = new MenuSQL();
		$menu_item_content = [];
		$menu_item         = [];

		foreach ($fields as $field) {
			$name  = $field['name'];
			$value = $field['value'];

			$name = trim(str_replace('item', '', $name), '- ');

			if (!$name) {
				continue;
			}

			if ($name === 'name') {
				$menu_item_content[$name] = strip_tags($value);
			} else {
				if ($name === 'content') {
					$menu_item_content[$name] = sanitizeHTML($value);
				} elseif ($name === 'img') {
				} else {
					$menu_item[$name] = $value;
				}
			}
		}

		$menu_item_content['language_id']      = self :: $global['language_id'];
		$menu_item['menu_item_id']             = $id;

		$menu_item_content['content'] ??= '';
		$menu_item['menu_item_content'][] = $menu_item_content;

		if ((isset($menu_item_content['name']) && $menu_item_content['name']) ||
			!empty($menu_item_content['content'])) {
			$result = $menu->editMenuItem(['menu_item' => $menu_item, 'menu_item_id' => $id]);
		}
	}
}
