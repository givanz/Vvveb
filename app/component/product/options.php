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
use Vvveb\System\Cart\Currency;
use Vvveb\System\Cart\Tax;
use Vvveb\System\Component\ComponentBase;
use Vvveb\System\Event;
use Vvveb\System\Images;

class Options extends ComponentBase {
	public static $defaultOptions = [
		'start'          => 0,
		'limit'			       => NULL,
		'site_id'        => NULL,
		'language_id'    => NULL,
		'product_id'     => 'url',
		'parent_id'      => NULL,
		'search'         => NULL,
	];

	function results() {
		$optionSql       = new Product_OptionSQL();
		$optionValuesSql = new Product_Option_ValueSQL();

		$results  = $optionSql->getAll($this->options);
		$values   = $optionValuesSql->getAll($this->options);
		$count 	  = 0;

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
						$value['price_tax']			    = $tax->addTaxes($value['price'], 1); //$product['tax_type_id']
						$value['price_formatted']	= $value['price_operator'] . $currency->format($value['price_tax']);
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
