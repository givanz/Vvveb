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

use Vvveb\System\Cart\Currency;
use Vvveb\System\Cart\Tax;
use Vvveb\System\Component\ComponentBase;
use Vvveb\System\Event;
use Vvveb\System\Images;
use function Vvveb\url;

class Wishlist extends ComponentBase {
	public static $defaultOptions = [
		'start'            => 0,
		'page'             => 1,
		'limit'            => 4,
		'status'           => 1,
		'language_id'      => null,
		'site_id'          => null,
		'type'             => 'product',
		'user_id'          => NULL,
		'taxonomy_item_id' => NULL,
		'image_size'       => 'medium',
		'filter'           => null,
	];

	public $options = [];

	function results() {
		$products = new \Vvveb\Sql\User_wishlistSQL();

		if ($page = $this->options['page']) {
			$this->options['start'] = ($page - 1) * $this->options['limit'];
		}

		if ($this->options['filter']) {
			foreach ($this->options['filter'] as $name => $values) {
				if ($name == 'manufacturer_id' || $name == 'vendor_id') {
					$this->options[$name] = $values;
				}
			}
		}

		if (isset($this->options['product_id']) &&
			($this->options['related'] || $this->options['variant'] || $this->options['source'] == 'autocomplete')) {
			if (! is_array($this->options['product_id'])) {
				$this->options['product_id'] = [$this->options['product_id'] => 1];
			}
			$this->options['product_id'] = array_keys($this->options['product_id']);
		} else {
			$this->options['product_id'] = [];
		}

		if (isset($this->options['order_by']) &&
				! in_array($this->options['order_by'], ['product_id', 'price', 'created_at', 'updated_at'])) {
			unset($this->options['order_by']);
		}

		if (isset($this->options['direction']) &&
				! in_array($this->options['direction'], ['asc', 'desc'])) {
			unset($this->options['direction']);
		}

		//if only one slug is provided then add it to array
		if (isset($this->options['slug']) && ! is_array($this->options['slug'])) {
			$this->options['slug'] = [$this->options['slug']];
		}

		//if only one manufacturer_id is provided then add it to array
		if (isset($this->options['manufacturer_id']) && ! is_array($this->options['manufacturer_id'])) {
			$this->options['manufacturer_id'] = [$this->options['manufacturer_id']];
		}

		//if only one vendor_id is provided then add it to array
		if (isset($this->options['vendor_id']) && ! is_array($this->options['vendor_id'])) {
			$this->options['vendor_id'] = [$this->options['vendor_id']];
		}

		$results = $products->getAll($this->options) + $this->options;

		if ($results && isset($results['product'])) {
			$tax      = Tax::getInstance($this->options);
			$currency = Currency::getInstance($this->options);

			foreach ($results['product'] as $id => &$product) {
				$language = [];

				if ($product['language_id'] != $this->options['default_language_id']) {
					$language = ['language' => $this->options['language']];
				}

				if (isset($product['images'])) {
					$product['images'] = json_decode($product['images'], true);

					foreach ($product['images'] as &$image) {
						$image['image'] = Images::image($image['image'], 'product', $this->options['image_size']);
					}
				}

				if (isset($product['image']) && $product['image']) {
					$product['image'] = Images::image($product['image'], 'product', $this->options['image_size']);
					//$product['images'][] = ['image' => Images::image($product['image'], 'product')];
				}

				//rfc
				$product['pubDate'] = date('r', strtotime($product['created_at']));

				$url                         = ['slug' => $product['slug'], 'product_id' => $product['product_id']] + $language;
				$product['url']      	       = url('product/product/index', $url);
				$product['add_cart_url']     = url('cart/cart/add', ['product_id' => $product['product_id']]);
				$product['buy_url']          = url('checkout/checkout/index', ['product_id' => $product['product_id']]);
				$product['add_wishlist_url'] = url('user/wishlist/add', ['product_id' => $product['product_id']]);
				$product['add_compare_url']  = url('cart/compare/add', ['product_id' => $product['product_id']]);
				$product['full-url']         = url('product/product/index', $url + ['host' => SITE_URL, 'scheme' => $_SERVER['REQUEST_SCHEME'] ?? 'http']);

				$product['price_tax']           = $tax->addTaxes($product['price'], $product['tax_type_id']);
				$product['price_tax_formatted'] = $currency->format($product['price_tax']);
				$product['price_formatted']     = $currency->format($product['price']);
			}
		}

		list($results) = Event :: trigger(__CLASS__,__FUNCTION__, $results);

		return $results;
	}
}
