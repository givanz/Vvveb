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
		$products           = $this->products;

		foreach ($products as $key => $product) {
			if (isset($product['option_value']) && $product['option_value']) {
				$product_options[$key] = $product['option_value'];
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
