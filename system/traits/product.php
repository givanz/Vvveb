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

namespace Vvveb\System\Traits;

use function Vvveb\__;
use function Vvveb\getCurrency;
use Vvveb\System\Cart\Currency;
use Vvveb\System\Cart\Tax;
use Vvveb\System\Images;
use function Vvveb\url;

trait Product {
	function products(&$products, $options = []) {
		if (! $products) {
			return $products;
		}

		if (! isset($this->currentCurrency)) {
			$this->currentCurrency = getCurrency();
			$this->tax             = Tax::getInstance($options);
			$this->currency        = Currency::getInstance($options);
		}

		foreach ($products as &$product) {
			$product = $this->product($product, $options);
		}

		return $products;
	}

	function product(&$product, $options = []) {
		$language        = [];

		if (! isset($this->currentCurrency)) {
			$this->currentCurrency = getCurrency();
			$this->tax             = Tax::getInstance($options);
			$this->currency        = Currency::getInstance($options);
		}

		if ($product['language_id'] != $options['default_language_id']) {
			$language          = ['language' => $options['language']];

			if (! $product['name']) {
				$product['name']   = '[' . __('No translation') . ']';
				$product['slug']   = 'no-translation';
			}
		}

		if (isset($product['images'])) {
			$product['images'] = json_decode($product['images'], true);

			foreach ($product['images'] as &$image) {
				$image['image'] = Images::image($image['image'], 'product', $options['image_size'] ?? 'medium');
			}
		}

		if (isset($product['image']) && $product['image']) {
			$product['image'] = Images::image($product['image'], 'product', $options['image_size'] ?? 'medium');
			//$product['images'][] = ['image' => Images::image($product['image'], 'product')];
		}

		//rfc
		$product['pubDate'] = date('r', strtotime($product['created_at']));
		$product['modDate'] = date('r', strtotime($product['updated_at']));
		$product['lastMod'] = date('Y-m-d\TH:i:sP', strtotime($product['updated_at']));

		$url                         = ['slug' => $product['slug'], 'product_id' => $product['product_id']] + $language;
		$product['url']      	       = url('product/product/index', $url);
		$product['add_cart_url']     = url('cart/cart/add', ['product_id' => $product['product_id']]);
		$product['buy_url']          = url('checkout/checkout/index', ['product_id' => $product['product_id']]);
		$product['add_wishlist_url'] = url('user/wishlist/add', ['product_id' => $product['product_id']]);
		$product['add_compare_url']  = url('cart/compare/add', ['product_id' => $product['product_id']]);
		$product['full-url']         = url('product/product/index', $url + ['host' => SITE_URL, 'scheme' => $_SERVER['REQUEST_SCHEME'] ?? 'http']);

		foreach (['price', 'old_price', 'min_price', 'max_price'] as $price) {
			$amount = 0;

			if (isset($product[$price]) && $product[$price]) {
				$amount = $product[$price];
			}
			$product["{$price}_tax"]            = $amount ? $this->tax->addTaxes($amount, $product['tax_type_id']) : 0;
			$product["{$price}_formatted"]      = $this->currency->format($amount);
			$product["{$price}_tax_formatted"]  = $this->currency->format($product["{$price}_tax"]);
			$product["{$price}_price_currency"] = $this->currentCurrency;
		}

		$product['has_variants'] = false;

		if (isset($product['min_price']) || isset($product['max_price'])) {
			$product['has_variants'] = true;
		}

		return $product;
	}
}
