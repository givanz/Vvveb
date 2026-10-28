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

use function Vvveb\session as sess;
use Vvveb\System\Locale;

/** @phpstan-consistent-constructor */
class Currency {
	/** @var array<string, array{sign_start: string, sign_end: string, decimal_place: int, value: float}> */
	private array $currencies = [];

	/** @var array<string, mixed> */
	private array $options = [];

	/**
	 * @param array<string, mixed> $options
	 */
	public static function getInstance(array $options = []): static {
		static $inst;

		if ($inst === null) {
			$inst = new static($options);
		}

		return $inst;
	}

	/**
	 * @param array<string, mixed> $options
	 */
	public function __construct(array $options = []) {
		$this->options    = $options;
		$this->currencies = Locale::availableCurrencies();
	}

	/**
	 * Number of decimal places for the given (or current) currency.
	 *
	 * @param string|false|null $currency Currency code, null/false = current.
	 *
	 * @return int
	 */
	public function getDecimalPlace(string|false|null $currency = null): int {
		if (!$currency) {
			$currency = $this->options['currency'] ?? sess('currency');
		}

		return (int)($this->currencies[$currency]['decimal_place'] ?? 2);
	}

	/**
	 * Format a numeric amount with the appropriate currency symbol.
	 *
	 * @param float|int|string $number
	 * @param string|false     $currency
	 * @param bool             $format
	 * @param float|int        $value
	 *
	 * @return string|float
	 */
	public function format(float|int|string $number, string|false $currency = false, bool $format = true, float|int $value = 0): string|float {
		if (!$currency) {
			$currency = $this->options['currency'] ?? sess('currency');
		}

		if (!$currency || !isset($this->currencies[$currency])) {
			return '';
		}

		$sign_start    = $this->currencies[$currency]['sign_start'];
		$sign_end      = $this->currencies[$currency]['sign_end'];
		$decimal_place = $this->currencies[$currency]['decimal_place'];

		if (!$value) {
			$value = $this->currencies[$currency]['value'];
		}

		$amount = $value ? (float)$number * $value : (float)$number;

		$amount = round($amount, $decimal_place);

		if (!$format) {
			return $amount;
		}

		$string = '';

		if ($sign_start) {
			$string .= $sign_start;
		}

		$decimals            = (int)$decimal_place;
		$decimal_separator   = '.';
		$thousands_separator = ',';

		$string .= number_format($amount, $decimals, $decimal_separator, $thousands_separator);

		if ($sign_end) {
			$string .= $sign_end;
		}

		return $string;
	}

	/**
	 * Convert a value between two currencies.
	 *
	 * @param float|int $value
	 * @param string    $from
	 * @param string    $to
	 */
	public function convert(float|int $value, string $from, string $to): float {
		$fromRate = $this->currencies[$from]['value'] ?? 1;
		$toRate   = $this->currencies[$to]['value'] ?? 1;

		if (!$fromRate) {
			return 0.0;
		}

		return $value * ($toRate / $fromRate);
	}
}
