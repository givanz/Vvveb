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

use function Vvveb\availableCurrencies;
use function Vvveb\session;

class Currency {
	private $driver;

	private $currencies;

	public static function getInstance($options = []) {
		static $inst = null;

		if ($inst === null) {
			$inst   = new Currency($options);
		}

		return $inst;
	}

	public function __construct($options = []) {
		$this->currencies = availableCurrencies();
	}

	public function format($number, $currency = false, $format = true, $value = 0) {
		if (! $currency) {
			$currency = session('currency');
		}

		if (! isset($this->currencies[$currency])) {
			return '';
		}

		$sign_start    = $this->currencies[$currency]['sign_start'];
		$sign_end      = $this->currencies[$currency]['sign_end'];
		$decimal_place = $this->currencies[$currency]['decimal_place'];

		if (! $value) {
			$value = $this->currencies[$currency]['value'];
		}

		$amount = $value ? (float)$number * $value : (float)$number;

		$amount = round($amount, $decimal_place);

		if (! $format) {
			return $amount;
		}

		$string = '';

		if ($sign_start) {
			$string .= $sign_start;
		}

		$decimals            = 2;
		$decimal_separator   = '.';
		$thousands_separator = ',';

		$string .= number_format($amount, $decimals, $decimal_separator, $thousands_separator);

		if ($sign_end) {
			$string .= $sign_end;
		}

		return $string;
	}

	public function convert($value, $from, $to) {
		if (isset($this->currencies[$from])) {
			$from = $this->currencies[$from]['value'];
		} else {
			$from = 1;
		}

		if (isset($this->currencies[$to])) {
			$to = $this->currencies[$to]['value'];
		} else {
			$to = 1;
		}

		return $value * ($to / $from);
	}
}
