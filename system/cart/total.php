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

class Total {
	function addTotal($key, $title, $cost, $text = '') {
		$data = ['title' => $title, 'cost' => $cost, 'cost_formatted' => '$' . $cost, 'text' => $text];

		$this->totals[$key] = $data;
		$this->write();
	}

	public function getGrandTotal() {
		$sum = 0;

		if ($this->totals) {
			foreach ($this->totals as $total) {
				$sum += $total['cost'];
			}
		}

		return $sum;
	}

	public function getGrandTotalFormatted() {
		$sum = $this->getGrandTotal();

		return '$' . $sum;
	}
}
