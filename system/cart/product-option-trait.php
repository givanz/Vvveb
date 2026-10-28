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

/**
 * Provides product option helpers for the cart.
 *
 * @property array<string, array{price: float|int|string, product_option_type_id: int|string}> $product_options
 * @property array<int|string, array<array-key, mixed>>                                        $products
 */
trait ProductOptionTrait {
	/** @var array<string, array{price: float|int|string, product_option_type_id: int|string}> */
	protected array $product_options = [];

	/**
	 * @param string           $name
	 * @param float|int|string $price
	 * @param int|string       $product_optionTypeId
	 */
	public function addProductOption(string $name, float|int|string $price, int|string $product_optionTypeId): void {
		if ($price && $product_optionTypeId) {
			$this->product_options[$name] = ['price' => $price, 'product_option_type_id' => $product_optionTypeId];
		}
	}

	/**
	 * @param string $name
	 */
	public function removeProductOption(string $name): void {
		unset($this->product_options[$name]);
	}

	/**
	 * Get product options for all products in the cart.
	 *
	 * @return array<int|string, mixed>
	 */
	public function getProductOptions(): array {
		$product_options = [];
		$products        = $this->products;

		foreach ($products as $key => $product) {
			if (isset($product['option_value']) && $product['option_value']) {
				$product_options[$key] = $product['option_value'];
			}
		}

		return $product_options;
	}

	/**
	 * Add product option totals to the cart totals.
	 */
	public function addProductOptionTotal(): void {
		$product_options = $this->getProductOptions();

		foreach ($product_options as $productKey => $optionValues) {
			if (!is_array($optionValues)) {
				continue;
			}

			foreach ($optionValues as $optionValueId => $optionValue) {
				if (!is_array($optionValue)) {
					continue;
				}

				$name  = $optionValue['name'] ?? '';
				$value = $optionValue['value'] ?? '';

				if ($name || $value) {
					$this->addTotal('product_option', 'product_option.' . $productKey . '.' . $optionValueId, $name, $value);
				}
			}
		}
	}
}
