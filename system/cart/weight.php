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

/** @phpstan-consistent-constructor */
class Weight {
	/** @var array<int, array{value: float, unit: string}> */
	private array $weightRates = [];

	public static function getInstance(): static {
		static $inst;

		if ($inst === null) {
			$inst = new static();
		}

		return $inst;
	}

	public function __construct() {
		$this->setWeight();
	}

	/**
	 * Load weight types from cache / database.
	 */
	public function setWeight(): void {
		$cache             = Cache::getInstance();
		$this->weightRates = $cache->cache(APP, 'weight_type', function(): array {
			$weightType = new Weight_typeSQL();
			$weights    = $weightType->getAll(['start' => 0, 'limit' => 100]);

			return $weights['weight_type'] ?? [];
		}, 259200);
	}

	public function clear(): void {
		$this->weightRates = [];
	}

	/**
	 * Convert a weight value between two weight types.
	 *
	 * @param float      $value
	 * @param string|int $from
	 * @param string|int $to
	 */
	public function convert(float $value, string|int $from, string|int $to): float {
		if ($from === $to) {
			return $value;
		}

		$fromRate = $this->weightRates[$from]['value'] ?? 1;
		$toRate   = $this->weightRates[$to]['value'] ?? 1;

		if (!$fromRate) {
			return 0.0;
		}

		return $value * ($toRate / $fromRate);
	}

	/**
	 * Format a weight value with its unit.
	 *
	 * @param float      $value
	 * @param string|int $weightTypeId
	 * @param string     $decimalPoint
	 * @param string     $thousandPoint
	 */
	public function format(float $value, string|int $weightTypeId, string $decimalPoint = '.', string $thousandPoint = ','): string {
		$unit = $this->weightRates[$weightTypeId]['unit'] ?? '';

		return number_format($value, 2, $decimalPoint, $thousandPoint) . $unit;
	}

	/**
	 * Get the unit string for a weight type.
	 *
	 * @param string|int $weightTypeId
	 */
	public function getUnit(string|int $weightTypeId): string {
		return $this->weightRates[$weightTypeId]['unit'] ?? '';
	}
}
