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

use \Vvveb\Sql\Product_Option_ValueSQL;
use \Vvveb\Sql\Product_OptionSQL;
use \Vvveb\Sql\Product_VariantSQL;
use \Vvveb\Sql\ProductSQL;
use Vvveb\System\Cart\Currency;
use Vvveb\System\Cart\Tax;
use Vvveb\System\Component\ComponentBase;
use Vvveb\System\Event;
use Vvveb\System\Images;

class Options extends ComponentBase {
	public static $defaultOptions = [
		'start'               => 0,
		'limit'               => NULL,
		'site_id'             => NULL,
		'language_id'         => NULL,
		'product_id'          => 'url',
		'product_variant_id'  => NULL,
		'parent_id'           => NULL,
		'search'              => NULL,
	];

	function results() {
		$productSql      = new ProductSQL();
		$optionSql       = new Product_OptionSQL();
		$optionValuesSql = new Product_Option_ValueSQL();
		$variantSql      = new Product_VariantSQL();

		$product = [];

		if ($this->options['product_id']) {
			$product = $productSql->get(['product_id' => $this->options['product_id']]);
		}

		$results  = $optionSql->getAll($this->options);
		$values   = $optionValuesSql->getAll($this->options);

		$voptions   = ['product_id' => $this->options['product_id'], 'start' => 0, 'limit' => 1];

		if ($this->options['product_variant_id']) {
			$voptions['product_variant_id'] = [$this->options['product_variant_id']];
		}

		$variants   = $variantSql->getAll($voptions);

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
			$tax      = Tax::getInstance($this->options);
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
						$defaultOptions[$value['product_option_id']] == $value['product_option_value_id']) {
						$value['checked'] = true;
					}

					if (isset($options[$value['product_option_id']])) {
						$options[$value['product_option_id']]['values'][] = $value;
					}
				}
			}
		}

		$results['count'] = $count;
		list($results)    = Event :: trigger(__CLASS__,__FUNCTION__, $results);

		return $results;
	}
}
