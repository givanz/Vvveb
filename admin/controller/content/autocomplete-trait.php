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

use \Vvveb\Sql\categorySQL;
use Vvveb\System\Images;
use function Vvveb\url;

trait AutocompleteTrait {
	function categoriesAutocomplete() {
		$categories = new CategorySQL();
		$text       = trim($this->request->get['text'] ?? '');
		$type       = trim($this->request->get['type'] ?? '');
		$post_type  = $this->request->get['post_type'] ?? '';

		$results = $categories->getCategories([
			'post_type' => $post_type,
			'type'      => $type,
			'search'    => '%' . $text . '%',
		] + $this->global);

		$search = [];

		if (isset($results['categories'])) {
			foreach ($results['categories'] as $category) {
				$search[$category['taxonomy_item_id']] = $category['name'];
			}
		}

		$this->response->setType('json');
		$this->response->output($search);
	}

	function manufacturersAutocomplete() {
		$manufacturers = new \Vvveb\Sql\ManufacturerSQL();
		$text          = trim($this->request->get['text'] ?? '');

		$options = [
			'search' => '%' . $text . '%',
		] + $this->global;

		$results = $manufacturers->getAll($options);

		$search = [];

		if (isset($results['manufacturer'])) {
			foreach ($results['manufacturer'] as $manufacturer) {
				$manufacturer['image']                    = Images::image($manufacturer['image'], 'manufacturer');
				$search[$manufacturer['manufacturer_id']] = '<img width="32" height="32" src="' . $manufacturer['image'] . '"> ' . $manufacturer['name'];
			}
		}

		$this->response->setType('json');
		$this->response->output($search);
	}

	function vendorsAutocomplete() {
		$vendors    = new \Vvveb\Sql\VendorSQL();
		$text       = trim($this->request->get['text'] ?? '');

		$options = [
			'search' => '%' . $text . '%',
		] + $this->global;

		$results = $vendors->getAll($options);

		$search = [];

		if (isset($results['vendor'])) {
			foreach ($results['vendor'] as $vendor) {
				$vendor['image']               = Images::image($vendor['image'], 'vendor');
				$search[$vendor['vendor_id']]  = '<img width="32" height="32" src="' . $vendor['image'] . '"> ' . $vendor['name'];
			}
		}

		$this->response->setType('json');
		$this->response->output($search);
	}

	function urlAutocomplete() {
		$products   = new \Vvveb\Sql\ProductSQL();
		$text       = trim($this->request->get['text'] ?? '');
		$type       = $this->request->get['type'] ?? 'card'; //'key-value'

		$options = [
			'limit' => 5,
			'like'  => $text,
		] + $this->global;

		unset($options['admin_id']);
		$results = $products->getAll($options);

		$search = [];

		if (isset($results['product'])) {
			foreach ($results['product'] as $product) {
				$product['image'] = Images::image($product['image'], 'product', 'thumb');
				$url              = url('product/product/index', ['slug'=> $product['slug'], 'product_id' => $product['product_id']]);

				if ($type == 'card') {
					$search[]         = [
						'type' => 'cardimage',
						'src'  => $product['image'],
						'text' => $product['name'],
						'value'=> '<a href="' . $url . '">' . $product['name'] . '</a>',
					];
				} else {
					$search[$url] = '<img width="32" height="32" src="' . $product['image'] . '"> ' . $product['name'];
				}
			}
		}

		if (count($search) < 5) {
			$posts   = new \Vvveb\Sql\PostSQL();
			$results = $posts->getAll($options);

			if (isset($results['post'])) {
				foreach ($results['post'] as $post) {
					$post['image'] = Images::image($post['image'], 'post', 'thumb');
					$url           = url('content/post/index', ['slug'=> $post['slug'], 'post_id' => $post['post_id']]);

					if ($type == 'card') {
						$search[]      = [
							'type' => 'cardimage',
							'src'  => $post['image'],
							'text' => $post['name'],
							'value'=> '<a href="' . $url . '">' . $post['name'] . '</a>',
						];
					} else {
						$search[$url] = '<img width="32" height="32" src="' . $post['image'] . '"> ' . $post['name'];
					}
				}
			}
		}

		$this->response->setType('json');
		$this->response->output($search);
	}

	function productsAutocomplete() {
		$products   = new \Vvveb\Sql\ProductSQL();
		$text       = trim($this->request->get['text'] ?? '');

		$options = [
			'like' => $text,
		] + $this->global;

		unset($options['admin_id']);
		$results = $products->getAll($options);

		$search = [];

		if (isset($results['product'])) {
			foreach ($results['product'] as $product) {
				$product['image']                        = Images::image($product['image'], $this->object);
				$search[$product[$this->object . '_id']] = '<img width="32" height="32" src="' . $product['image'] . '"> ' . $product['name'];
			}
		}

		$this->response->setType('json');
		$this->response->output($search);
	}

	function adminsAutocomplete() {
		$admins     = new \Vvveb\Sql\AdminSQL();
		$text       = trim($this->request->get['text'] ?? '');

		$options = [
			'status' => 1,
			'search' => $text,
		] + $this->global;

		$results = $admins->getAll($options);

		$search = [];

		if (isset($results['admin'])) {
			foreach ($results['admin'] as $admin) {
				$text = '';

				if (isset($admin['avatar'])) {
					$admin['avatar'] = Images::image($admin['avatar'], 'admin');
					$text            =  '<img width="32" height="32" src="' . $admin['avatar'] . '"> ';
				}
				$text .= $admin['username'] . ' (' . $admin['first_name'] . ' ' . $admin['last_name'] . ')';

				$search[$admin['admin_id']] =  $text;
			}
		}

		$this->response->setType('json');
		$this->response->output($search);
	}

	function attributesAutocomplete() {
		$attributes = new \Vvveb\Sql\AttributeSQL();
		$text       = trim($this->request->get['text'] ?? '');

		$options = [
			'search' => $text,
		] + $this->global;

		$results = $attributes->getAll($options);

		$search = [];

		if (isset($results['attribute'])) {
			foreach ($results['attribute'] as $attribute) {
				$text = '';

				$text .= $attribute['name'] . ' (' . $attribute['group'] . ')';

				$search[$attribute['attribute_id']] =  $text;
			}
		}

		$this->response->setType('json');
		$this->response->output($search);
	}

	function optionValuesAutocomplete() {
		$values = new \Vvveb\Sql\Option_valueSQL();

		$options = [
			'option_id'      => trim($this->request->get['option_id']),
		] + $this->global;

		$results = $values->getAll($options);

		$search = [];

		if (isset($results['option_value'])) {
			foreach ($results['option_value'] as $value) {
				$text = '';

				$text .= $value['name'];

				$search[$value['option_value_id']] =  $text;
			}
		}

		$this->response->setType('json');
		$this->response->output($search);
	}

	function digitalAssetsAutocomplete() {
		$digital_assets = new \Vvveb\Sql\Digital_assetSQL();
		$text           = trim($this->request->get['text'] ?? '');

		$options = [
			'search' => $text,
		] + $this->global;

		$results = $digital_assets->getAll($options);

		$search = [];

		if (isset($results['digital_asset'])) {
			foreach ($results['digital_asset'] as $digital_asset) {
				$text = '';

				$text .= $digital_asset['name'] . ' (' . $digital_asset['file'] . ')';

				$search[$digital_asset['digital_asset_id']] =  $text;
			}
		}

		$this->response->setType('json');
		$this->response->output($search);
	}
}
