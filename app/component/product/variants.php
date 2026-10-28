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

namespace Vvveb\Component\Product;

use function Vvveb\__;
use Vvveb\Sql\Product_variantSQL;
use Vvveb\Sql\ProductSQL;
use Vvveb\System\Cart\Currency;
use Vvveb\System\Cart\Tax;
use Vvveb\System\Component\ComponentBase;
use Vvveb\System\Event;
use Vvveb\System\Images;
use Vvveb\System\Locale;
use function Vvveb\url;

class Variants extends ComponentBase {
	public static array $defaultOptions = [
		'start'       => 0,
		'limit'       => null,
		'site_id'     => null,
		'language_id' => null,
		'product_id'  => 'url',
		'parent_id'   => null,
		'search'      => null,
		'name'        => null,
		'url'         => null,
	];

	public int $cacheExpire = 0;

	/**
	 * Get product variants with pricing, tax, option names, and URLs.
	 *
	 * @return array<string, mixed> Variants data with count and product_variant list.
	 */
	public function results(): array {
		$productSQL      = new ProductSQL();
		$variantSQL      = new Product_variantSQL();

		$product  = [];
		$variants = [];
		$count 	  = 0;

		if (isset($this->options['product_id']) && $this->options['product_id']) {
			$product  = $productSQL->get(['product_id' => $this->options['product_id']]);
			$variants = $product['product_variant'] ?? [];
		}

		if ($variants) {
			$language = [];

			if (($product['language_id'] != ($this->options['default_language_id'] ?? null))) {
				$language = ['language' => $this->options['language']];

				if ($product['name'] === null && isset($product['product_content'][$this->options['default_language_id'] ?? null])) {
					$langFallback            = $product['product_content'][$this->options['default_language_id'] ?? null];
					$product['name']         = $langFallback['name'];
					$product['slug']         = $langFallback['slug'];
					$product['content']      = $langFallback['content'];
					$product['language_id']  = $langFallback['language_id'];
				} else {
					if (!$product['name']) {
						$product['name']   = '[' . __('No translation') . ']';
					}
				}
			} else {
				if ($this->options['default_lang_slug']) {
					$language = ['language' => $this->options['language']];
				}
			}

			$optionIds = [];

			if (isset($this->options['name'])) {
				if (isset($product['option'])) {
					foreach ($product['option'] as $id => &$option) {
						unset($option['array_key']);
					}
				} else {
					$product['option'] = [];
				}

				if (isset($product['product_option'])) {
					$product_option_value = &$product['product_option_value'];
					$product_option       = &$product['product_option'];

					$option_value_content = [];
					$names                = [];

					$default = '';

					if ($product['language_id'] != $this->options['default_language_id']) {
						$default = '[' . __('No translation') . ']';
					}

					foreach ($product['option_value_content'] as &$value) {
						$option_value_content[$value['option_id']][$value['option_value_id']] = $value;
						$names[$value['option_value_id']]                                     = $value['name'] ?? $default;
					}

					foreach ($product_option_value as &$value) {
						if (isset($product_option[$value['product_option_id']])) {
							$product_option[$value['product_option_id']]['values'][$value['product_option_value_id']] = $value;
							$optionIds[$value['product_option_id']][$value['product_option_value_id']]                = $names[$value['option_value_id']] ?? $default;
						}
					}

					$product['option_value_content'] = $option_value_content;
				} else {
					$product['product_option'] = [];
				}
			}

			$tax             = Tax::getInstance();
			$currency        = Currency::getInstance($this->options);
			$currentCurrency = Locale :: getCurrency();

			$url = $language + ['host' => SITE_URL, 'scheme' => $_SERVER['REQUEST_SCHEME'] ?? 'http'];

			if ($product['type'] != 'product') {
				$url['type'] = $product['type'];
			}

			//if translation is missing slug is not available
			if (isset($product['slug'])) {
				$url['slug'] = $product['slug'];
			} else {
				$url['product_id'] = $product['product_id'];
			}

			$product['url'] = url('product/product/index', $url);

			foreach ($variants as $opts => &$variant) {
				$count++;

				if ($variant['image']) {
					$variant['image'] = Images::image($variant['image'], 'option', $this->options['image_size']);
				}

				if ($variant['price']) {
					$variant['price_tax']           = $tax->addTaxes($variant['price'], $product['tax_type_id'] ?? 0);
					$variant['price_formatted']     = $currency->format($variant['price']);
					$variant['price_tax_formatted'] = $currency->format($variant['price_tax']);
					$variant['price_currency']      = $currentCurrency;
				}

				if (isset($this->options['name'])) {
					$keys = explode(',', $opts);
					$name = '';
					foreach ($keys as $key) {
						$r = explode(':', $key);
						$o = $r[0];
						$v = $r[1];

						if ($name) {
							$name .= ' / ';
						}

						$name .= $optionIds[$o][$v];
					}

					$variant['name'] = $product['name'] . ' - ' . $name;
					$variant['url']  = $product['url'] . '?product_variant_id=' . $variant['product_variant_id'];
				}
			}
		}

		$results['count']           = $count;
		$results['product_variant'] = $variants;

		[$results]    = Event :: trigger(__CLASS__, __FUNCTION__, $results);

		return $results;
	}
}
