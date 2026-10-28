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
 * Provides cart total management.
 *
 * @property Currency                                                                                                                                  $currency
 * @property array<int|string, array<array-key, mixed>>                                                                                                $products
 * @property array<string, array{namespace: string, key: string, title: string, value: float|int|string, value_formatted: string|float, text: string}> $totals
 */
trait TotalTrait {
	/** @var array<string, array{namespace: string, key: string, title: string, value: float|int|string, value_formatted: string|float, text: string}> */
	protected array $totals = [];

	/**
	 * Add a total entry.
	 *
	 * @param string           $namespace
	 * @param string           $key
	 * @param string           $title
	 * @param float|int|string $value
	 * @param string           $text
	 */
	public function addTotal(string $namespace, string $key, string $title, float|int|string $value, string $text = ''): void {
		$data = ['namespace' => $namespace, 'key' => $key, 'title' => $title, 'value' => $value, 'value_formatted' => $this->currency->format($value), 'text' => $text];

		$this->totals[$key] = $data;
	}

	/**
	 * Remove totals by namespace and optionally by key.
	 *
	 * @param string       $namespace
	 * @param string|false $key
	 */
	public function removeTotal(string $namespace, string|false $key = false): void {
		if ($key) {
			unset($this->totals[$key]);
		} else {
			foreach ($this->totals as $key => $total) {
				if ($total['namespace'] === $namespace) {
					unset($this->totals[$key]);
				}
			}
		}
	}

	/**
	 * Calculate the sub-total from all products.
	 */
	public function getSubTotal(): float|int {
		$total = 0;

		foreach ($this->products as $product) {
			$total += $product['total'] ?? 0;
		}

		return $total;
	}

	/**
	 * Get totals filtered by namespace and/or key.
	 *
	 * @param string|false $namespace
	 * @param string|false $key
	 *
	 * @return array<string, mixed> Matching totals (single row when $key is given).
	 */
	public function getTotals(string|false $namespace = false, string|false $key = false): array {
		if ($key) {
			return $this->totals[$key] ?? [];
		} elseif ($namespace) {
			$totals = [];
			foreach ($this->totals as $key => $total) {
				if ($total['namespace'] === $namespace) {
					$totals[$key] = $total;
				}
			}

			return $totals;
		}

		return $this->totals;
	}

	/**
	 * Calculate and return all totals including taxes and coupons.
	 *
	 * @return array<string, array{namespace: string, key: string, title: string, value: float|int|string, value_formatted: string|float, text: string}>
	 */
	public function getAllTotals(): array {
		//include taxes
		$this->addTaxTotal();
		$this->addCouponTotal();

		return $this->totals;
	}

	/**
	 * Calculate the grand total from all totals.
	 */
	public function getGrandTotal(): float {
		$sum = 0;

		if ($this->totals) {
			foreach ($this->totals as $total) {
				$sum += (float)($total['value'] ?? 0);
			}
		}

		return $sum;
	}

	/**
	 * Get the grand total formatted with the current currency.
	 */
	public function getGrandTotalFormatted(): string|float {
		$sum = $this->getGrandTotal();

		return $this->currency->format($sum);
	}
}
