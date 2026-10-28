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

use Vvveb\Sql\Product_option_valueSQL;
use Vvveb\Sql\Product_optionSQL;
use Vvveb\Sql\Product_variantSQL;
use Vvveb\Sql\ProductSQL;
use Vvveb\System\Cart\Currency;
use Vvveb\System\Cart\Tax;
use Vvveb\System\Component\ComponentBase;
use Vvveb\System\Event;
use Vvveb\System\Images;

class Options extends ComponentBase {
	public static array $defaultOptions = [
		'start'              => 0,
		'limit'              => null,
		'site_id'            => null,
		'language_id'        => null,
		'product_id'         => 'url',
		'product_variant_id' => null,
		'parent_id'          => null,
		'search'             => null,
		'image_size'         => 'thumb',
	];

	public int $cacheExpire = 0;

	/**
	 * Get product options with option values, pricing, tax, and default variant selection.
	 *
	 * @return array<string, mixed> Options data with count and product_option values.
	 */
	public function results(): array {
		$productSQL      = new ProductSQL();
		$optionSQL       = new Product_optionSQL();
		$optionvaluesSQL = new Product_option_valueSQL();
		$variantSQL      = new Product_variantSQL();

		$product = [];
		$count   = 0;

		if (isset($this->options['product_id']) && $this->options['product_id']) {
			$product = $productSQL->get(['product_id' => $this->options['product_id']]);

			$results  = $optionSQL->getAll($this->options);
			$values   = $optionvaluesSQL->getAll($this->options);

			$voptions   = ['product_id' => $this->options['product_id'], 'start' => 0, 'limit' => 1];

			if (isset($this->options['product_variant_id'])) {
				$voptions['product_variant_id'] = [$this->options['product_variant_id']];
			}

			$variants   = $variantSQL->getAll($voptions);

			$defaultVariant = [];
			$defaultOptions = [];

			if ($variants && isset($variants['product_variant'])) {
				$defaultVariant = current($variants['product_variant']);
			}

			if ($defaultVariant) {
				$json           = '{' . preg_replace('/(\d+)/', '"\1"', $defaultVariant['options']) . '}';
				$defaultOptions = json_decode($json, true);
			}

			$count = 0;

			if ($results && isset($results['product_option'])) {
				$options  = &$results['product_option'];
				$tax      = Tax::getInstance();
				$currency = Currency::getInstance($this->options);

				if ($values && isset($values['product_option_value'])) {
					foreach ($values['product_option_value'] as &$value) {
						$count++;

						if ($value['image']) {
							$value['image'] = Images::image($value['image'], 'option', $this->options['image_size']);
						}

						if ($value['price']) {
							$value['price_tax']       = $tax->addTaxes($value['price'], $product['tax_type_id'] ?? 0);
							$value['price_formatted'] = $value['price_operator'] . $currency->format($value['price_tax']);
						}

						if (isset($defaultOptions[$value['product_option_id']]) &&
							$defaultOptions[$value['product_option_id']] === $value['product_option_value_id']) {
							$value['checked'] = true;
						}

						if (isset($options[$value['product_option_id']])) {
							$options[$value['product_option_id']]['values'][] = $value;
						}
					}
				}
			}
		}

		$results['count'] = $count;
		[$results]        = Event :: trigger(__CLASS__, __FUNCTION__, $results);

		return $results;
	}
}
