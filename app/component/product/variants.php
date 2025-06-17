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

use \Vvveb\Sql\Product_VariantSQL;
use \Vvveb\Sql\ProductSQL;
use Vvveb\System\Cart\Currency;
use Vvveb\System\Cart\Tax;
use Vvveb\System\Component\ComponentBase;
use Vvveb\System\Event;
use Vvveb\System\Images;

class Variants extends ComponentBase {
	public static $defaultOptions = [
		'start'       => 0,
		'limit'       => NULL,
		'site_id'     => NULL,
		'language_id' => NULL,
		'product_id'  => 'url',
		'parent_id'   => NULL,
		'search'      => NULL,
	];

	function results() {
		$productSql      = new ProductSQL();
		$variantSql      = new Product_VariantSQL();

		$product = [];

		if ($this->options['product_id']) {
			$product = $productSql->get(['product_id' => $this->options['product_id']]);
		}

		$results  = $variantSql->getAll($this->options);
		$count 	  = 0;

		if ($results && isset($results['product_variant'])) {
			$variants  = &$results['product_variant'];
			$tax       = Tax::getInstance($this->options);
			$currency  = Currency::getInstance($this->options);

			if ($variants) {
				foreach ($variants as &$variant) {
					$count++;

					if ($variant['image']) {
						$variant['image'] = Images::image($variant['image'], 'option', $this->options['image_size']);
					}

					if ($variant['price']) {
						$variant['price_tax']       = $tax->addTaxes($variant['price'], $product['tax_type_id'] ?? 0);
						$variant['price_formatted'] = $currency->format($variant['price_tax']);
					}
				}
			}
		}

		$results['count'] = $count;
		list($results)    = Event :: trigger(__CLASS__,__FUNCTION__, $results);

		return $results;
	}
}
