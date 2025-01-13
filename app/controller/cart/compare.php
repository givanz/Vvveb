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

namespace Vvveb\Controller\Cart;

use \Vvveb\Sql\AttributeSQL;
use \Vvveb\Sql\ProductSQL;
use Vvveb\Controller\Base;
use function Vvveb\session as sess;
use Vvveb\System\Cart\Currency;
use Vvveb\System\Cart\Tax;
use Vvveb\System\Images;
use function Vvveb\url;

class Compare extends Base {
	function index() {
		$product_id = $compare = sess('compare'); //[18, 19, 17, 16];
		$results    = [];
		$names      = [];
		$specs      = [];

		if ($product_id) {
			$names = [];

			$prod     = new ProductSQL();
			$results  = $prod->getAll(['product_id' => $product_id] + $this->global);

			$category    = new AttributeSQL();
			$attributes  = $category->getAll(['product_id' => $product_id] + $this->global);

			if (isset($attributes['attribute'])) {
				foreach ($attributes['attribute'] as $attr) {
					$attrs[$attr['product_id']][$attr['attribute_id']] = $attr['value'];
					$names[$attr['attribute_id']]                      = $attr['name'];
				}
			}

			if ($results && isset($results['product'])) {
				$tax      = Tax::getInstance($this->global);
				$currency = Currency::getInstance($this->global);

				foreach ($results['product'] as $id => &$product) {
					if (isset($product['image']) && $product['image']) {
						$product['image'] = Images::image($product['image'], 'product', 'thumb');
					}

					$url                         = ['slug' => $product['slug'], 'product_id' => $product['product_id']];
					$product['url']      	       = url('product/product/index', $url);
					$product['add_cart_url']     = url('cart/cart/add', ['product_id' => $product['product_id']]);
					$product['remove_url']       = url('cart/compare/remove', ['product_id' => $product['product_id']]);
					$product['buy_url']          = url('checkout/checkout/index', ['product_id' => $product['product_id']]);

					$product['price_tax']           = $tax->addTaxes($product['price'], $product['tax_type_id']);
					$product['price_tax_formatted'] = $currency->format($product['price_tax']);
					$product['price_formatted']     = $currency->format($product['price']);
				}

				$specs = [];

				foreach ($names as $attribute_id => $name) {
					foreach ($results['product'] as $prod) {
						$id                        = $prod['product_id'];
						$specs[$attribute_id][$id] = $attrs[$id][$attribute_id] ?? '-';
					}
				}
			}
		}

		$this->view->products = $results;
		$this->view->names    = $names;
		$this->view->specs    = $specs;
	}

	private function action($action) {
		$productId = (int)($this->request->get['product_id'] ?? $this->request->post['product_id'] ?? false);

		if ($productId) {
			$compare = sess('compare');

			switch ($action) {
				case 'add':
					$compare[$productId] = $productId;

				break;

				case 'remove':
					unset($compare[$productId]);

				break;
			}

			sess(['compare' => $compare]);
		}

		$this->index();
	}

	function add() {
		return $this->action('add');
	}

	function remove() {
		return $this->action('remove');
	}
}
