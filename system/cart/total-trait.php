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

trait TotalTrait {
	protected $totals = [];

	function addTotal($key, $title, $value, $text = '') {
		$data = ['key' => $key, 'title' => $title, 'value' => $value, 'value_formatted' => $this->currency->format($value), 'text' => $text];

		$this->totals[$key] = $data;
		$this->write();
	}

	function removeTotal($key) {
		unset($this->totals[$key]);
	}

	public function getSubTotal() {
		$total = 0;

		foreach ($this->products as $product) {
			$total += $product['total'];
		}

		return $total;
	}

	function getTotals() {
		//include taxes
		$this->addTaxTotal();
		$this->addCouponTotal();

		return $this->totals;
	}

	public function getGrandTotal() {
		$sum = 0;

		if ($this->totals) {
			foreach ($this->totals as $total) {
				$sum += (float)($total['value'] ?? 0);
			}
		}

		return $sum;
	}

	public function getGrandTotalFormatted() {
		$sum = $this->getGrandTotal();

		return $this->currency->format($sum);
	}
}
