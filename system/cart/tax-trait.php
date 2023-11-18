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

trait TaxTrait {
	public function addTax($name, $price, $taxTypeId) {
		if ($price && $taxTypeId) {
			$this->taxes[$name] = ['price' => $price, 'tax_type_id' => $taxTypeId];
		}
	}

	public function removeTax($name) {
		unset($this->taxes[$name]);
	}

	public function getTaxes() {
		$taxes    = [];
		$products = $this->products + $this->taxes;

		foreach ($products as $product) {
			if ($product['tax_type_id']) {
				$tax_rates = $this->tax->getRates($product['price'], $product['tax_type_id']);

				foreach ($tax_rates as $tax_rate) {
					if (! isset($taxes[$tax_rate['tax_rate_id']])) {
						$taxes[$tax_rate['tax_rate_id']]          = $tax_rate;
						$taxes[$tax_rate['tax_rate_id']]['value'] = 0;
					}

					$taxes[$tax_rate['tax_rate_id']]['value'] += ($tax_rate['amount'] * ($product['quantity'] ?? 1));
				}
			}
		}

		return $taxes;
	}

	function addTaxTotal() {
		$taxes = $this->getTaxes();

		foreach ($taxes as $tax) {
			$this->addTotal('tax.' . $tax['tax_rate_id'], $tax['name'], $tax['value']);
		}
	}
}
