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
use Vvveb\System\Cart\Currency;
use Vvveb\System\Cart\Tax;
use Vvveb\System\Images;
use Vvveb\System\Locale;
use function Vvveb\url;

trait Product {
	/**
	 * Currency code for the current session (e.g. 'USD', 'EUR').
	 *
	 * @var string
	 */
	protected string $currentCurrency = '';

	/**
	 * Tax calculator instance.
	 *
	 * @var \Vvveb\System\Cart\Tax
	 */
	protected ?Tax $tax = null;

	/**
	 * Currency formatter instance.
	 *
	 * @var \Vvveb\System\Cart\Currency
	 */
	protected ?Currency $currency = null;

	/**
	 * Process an array of products through the single-product formatter.
	 *
	 * @param list<array<string, mixed>>|null $products Array of product data arrays, passed by reference.
	 * @param array<string, mixed>            $options  Formatting options (image_size, default_language_id, etc.).
	 *
	 * @return list<array<string, mixed>>|null The formatted products array<array-key, mixed>.
	 */
	public function products(?array &$products, array $options = []): ?array {
		if (!$products) {
			return $products;
		}

		if (!isset($this->currentCurrency) || $this->currentCurrency === '') {
			$this->currentCurrency = Locale::getCurrency();
			$this->tax             = Tax::getInstance();
			$this->currency        = Currency::getInstance($options);
		}

		foreach ($products as &$product) {
			$product = $this->product($product, $options);
		}

		return $products;
	}

	/**
	 * Format a single product with image URLs, prices, taxes, and URLs.
	 *
	 * @param array<string, mixed> $product The raw product data array, passed by reference.
	 * @param array<string, mixed> $options Formatting options. Expected keys include:
	 *                                      - default_language_id (int): fallback language ID.
	 *                                      - default_lang_slug (string): fallback language slug.
	 *                                      - language (string): current language slug.
	 *                                      - image_size (string): image size variant.
	 *                                      - image_resize (string): resize mode ('cs' = crop/scale).
	 *                                      - tax_type_id (int): tax type identifier.
	 *
	 * @return array<string, mixed> The formatted product data.
	 */
	public function product(array &$product, array $options = []): array {
		$language = [];

		if (!isset($this->currentCurrency) || $this->currentCurrency === '') {
			$this->currentCurrency = Locale::getCurrency();
			$this->tax             = Tax::getInstance();
			$this->currency        = Currency::getInstance($options);
		}

		if (($product['language_id'] != $options['default_language_id'])) {
			$language = ['language' => $options['language']];

			if ($product['name'] === null && isset($product['product_content'][$options['default_language_id']])) {
				$langFallback            = $product['product_content'][$options['default_language_id']];
				$product['name']         = $langFallback['name'];
				$product['slug']         = $langFallback['slug'];
				$product['content']      = $langFallback['content'];
				$product['language_id']  = $langFallback['language_id'];
			} else {
				if (!$product['name']) {
					$product['name'] = '[' . __('No translation') . ']';
				}
			}
		} else {
			if ($options['default_lang_slug'] ?? '') {
				$language = ['language' => $options['language']];
			}
		}

		if (isset($product['images'])) {
			$product['images'] = json_decode($product['images'], true);

			foreach ($product['images'] as &$image) {
				$image['image'] = Images::image($image['image'], 'product', $options['image_size'] ?? 'medium', $options['image_resize'] ?? 'cs');
			}
		}

		if (isset($product['image']) && $product['image']) {
			$product['image'] = Images::image($product['image'], 'product', $options['image_size'] ?? 'medium', $options['image_resize'] ?? 'cs');
		}

		//rfc
		$createdTime        = strtotime((string)($product['created_at'] ?? '')) ?: time();
		$updatedTime        = strtotime((string)($product['updated_at'] ?? '')) ?: $createdTime;
		$product['pubDate'] = date('r', $createdTime);
		$product['modDate'] = date('r', $updatedTime);
		$product['lastMod'] = date('Y-m-d\TH:i:sP', $updatedTime);

		$url = $language;

		if (($product['type'] ?? 'product') !== 'product') {
			$url['type'] = $product['type'];
		}

		//if translation is missing slug is not available
		if (isset($product['slug'])) {
			$url['slug'] = $product['slug'];
		} else {
			$url['product_id'] = $product['product_id'];
		}

		$product['url']              = url('product/product/index', $url);
		$product['add_cart_url']     = url('cart/cart/add', ['product_id' => $product['product_id']]);
		$product['buy_url']          = url('checkout/checkout/index', ['product_id' => $product['product_id']]);
		$product['add_wishlist_url'] = url('user/wishlist/add', ['product_id' => $product['product_id']]);
		$product['add_compare_url']  = url('cart/compare/add', ['product_id' => $product['product_id']]);
		$product['full-url']         = url('product/product/index', $url + ['host' => SITE_URL, 'scheme' => $_SERVER['REQUEST_SCHEME'] ?? 'http']);

		foreach (['price', 'old_price', 'min_price', 'max_price'] as $price) {
			$amount = 0;

			if (isset($product[$price]) && $product[$price]) {
				$amount = (float)$product[$price];
			}
			$product["{$price}_tax"]           = $amount ? $this->tax->addTaxes($amount, $product['tax_type_id'] ?? 0) : 0;
			$product["{$price}_formatted"]     = $this->currency->format($amount);
			$product["{$price}_tax_formatted"] = $this->currency->format($product["{$price}_tax"]);
			$product["{$price}_currency"]      = $this->currentCurrency;
		}

		$product['has_variants'] = false;

		if (isset($product['min_price']) || isset($product['max_price'])) {
			$product['has_variants'] = true;
		}

		return $product;
	}
}
