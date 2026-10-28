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
 * Provides tax calculation helpers for the cart.
 *
 * @property array<string, array{price: float|int|string, tax_type_id: int|string}> $taxes
 * @property array<int|string, array<array-key, mixed>>                             $products
 * @property float|int                                                              $total_tax
 * @property Tax                                                                    $tax
 */
trait TaxTrait {
	/**
	 * Split a tax-inclusive amount using the cart's resolved rates.
	 *
	 * @param float|int|string $gross
	 * @param int|string       $taxTypeId
	 *
	 * @return array{0: float, 1: float} [net, tax]
	 */
	public function extractTax(float|int|string $gross, int|string $taxTypeId): array {
		return $this->tax->extractTax($gross, $taxTypeId);
	}

	/**
	 * @param string           $name
	 * @param float|int|string $price
	 * @param int|string       $taxTypeId
	 */
	public function addTax(string $name, float|int|string $price, int|string $taxTypeId): void {
		if ($price && $taxTypeId) {
			$this->taxes[$name] = ['price' => $price, 'tax_type_id' => $taxTypeId];
		}
	}

	/**
	 * @param string $name
	 */
	public function removeTax(string $name): void {
		unset($this->taxes[$name]);
	}

	/**
	 * Calculate taxes for all products and custom taxes.
	 *
	 * @return array<int, array{tax_rate_id: int|string, name: string, rate: string, type: string, amount: float|int, value: float|int}>
	 */
	public function getTaxes(): array {
		$taxes           = [];
		$this->total_tax = 0;
		$products        = $this->products + $this->taxes;
		$precision       = $this->tax->taxPrecision ?? 2;

		foreach ($products as $product) {
			if (isset($product['tax_type_id']) && $product['tax_type_id']) {
				$tax_rates = $this->tax->getRates($product['price'], $product['tax_type_id']);

				foreach ($tax_rates as $tax_rate) {
					$rateId = $tax_rate['tax_rate_id'];

					if (!isset($taxes[$rateId])) {
						$taxes[$rateId]          = $tax_rate;
						$taxes[$rateId]['value'] = 0;
					}

					//round per order line so the summed total matches the
					//displayed line taxes instead of drifting by a cent
					$lineTax               = round($tax_rate['amount'] * ($product['quantity'] ?? 1), $precision);
					$taxes[$rateId]['value'] += $lineTax;
					$this->total_tax += $lineTax;
				}
			}
		}

		return $taxes;
	}

	/**
	 * Get the total tax amount.
	 */
	public function getTaxTotal(): float|int {
		return $this->total_tax;
	}

	/**
	 * Calculate and add all tax totals to the cart totals.
	 */
	public function addTaxTotal(): void {
		$taxes = $this->getTaxes();
		$this->removeTotal('tax');

		foreach ($taxes as $tax) {
			$this->addTotal('tax', 'tax.' . $tax['tax_rate_id'], $tax['name'], $tax['value']);
		}
	}
}
