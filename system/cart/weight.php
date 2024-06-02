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

use Vvveb\Sql\Weight_typeSQL;
use Vvveb\System\Cache;

class Weight {
	private $weightRates = [];

	public static function getInstance($options = []) {
		static $inst = null;

		if ($inst === null) {
			$inst   = new Weight($options);
		}

		return $inst;
	}

	public function __construct($options = []) {
		$this->setWeight();
	}

	public function setWeight() {
		$cache      = Cache::getInstance();
		$weights    = $cache->cache(APP,'weight_type' ,function () {
			$weightType = new Weight_typeSQL();
			$weights = $weightType->getAll(['start' => 0, 'limit' => 100]);

			return $weights['weight_type'] ?? [];
		}, 259200);
	}

	public function clear() {
		$this->weightRates = [];
	}

	public function convert($value, $from, $to) : float {
		if ($from == $to) {
			return $value;
		}

		if (isset($this->weightRates[$from])) {
			$from = $this->weightRates[$from]['value'];
		} else {
			$from = 1;
		}

		if (isset($this->weightRates[$to])) {
			$to = $this->weightRates[$to]['value'];
		} else {
			$to = 1;
		}

		return $value * ($to / $from);
	}

	public function format($value, $weightTypeId, $decimalPoint = '.', $thousandPoint = ',') {
		$unit = $this->weightRates[$weightTypeId]['unit'] ?? '';

		return number_format($value, 2, $decimalPoint, $thousandPoint) . $unit;
	}

	public function getUnit($weightTypeId) {
		$this->weightRates[$weightTypeId]['unit'] ?? '';
	}
}
