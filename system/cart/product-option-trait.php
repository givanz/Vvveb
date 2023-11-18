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

namespace Vvveb\System\Cart;

trait ProductOptionTrait {
	protected $product_options = [];

	public function addProductOption($name, $price, $product_optionTypeId) {
		if ($price && $product_optionTypeId) {
			$this->product_options[$name] = ['price' => $price, 'product_option_type_id' => $product_optionTypeId];
		}
	}

	public function removeProductOption($name) {
		unset($this->product_options[$name]);
	}

	public function getProductOptions() {
		$product_options    = [];
		$products           = $this->products + $this->product_options;

		foreach ($products as $product) {
			if ($product['product_option_type_id']) {
				$product_option_rates = $this->product_option->getRates($product['price'], $product['product_option_type_id']);

				foreach ($product_option_rates as $product_option_rate) {
					if (! isset($product_options[$product_option_rate['product_option_rate_id']])) {
						$product_options[$product_option_rate['product_option_rate_id']]          = $product_option_rate;
						$product_options[$product_option_rate['product_option_rate_id']]['value'] = 0;
					}

					$product_options[$product_option_rate['product_option_rate_id']]['value'] += ($product_option_rate['amount'] * ($product['quantity'] ?? 1));
				}
			}
		}

		return $product_options;
	}

	function addProductOptionTotal() {
		$product_options = $this->getProductOptions();

		foreach ($product_options as $product_option) {
			$this->addTotal('product_option.' . $product_option['product_option_rate_id'], $product_option['name'], $product_option['value']);
		}
	}
}
