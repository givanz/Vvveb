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

use Vvveb\Sql\Tax_TypeSQL;
use Vvveb\System\Cache;

class Tax {
	private $driver;

	private $taxRates = [];

	public static function getInstance($options = []) {
		static $inst = null;

		if ($inst === null) {
			$inst   = new Tax($options);
		}

		return $inst;
	}

	public function __construct($options = []) {
	}

	public function setRegionRules($country_id, $region_id, $based = 'store') {
		$cache     = Cache::getInstance();
		$rules     = $cache->cache(APP,"tax_rates.$country_id.$region_id.$based",function () use ($country_id, $region_id, $based) {
			$taxType = new Tax_TypeSQL();
			$taxRules = $taxType->getRegionRules(['country_id' => $country_id, 'region_id' => $region_id, 'based' => $based]);

			return $taxRules['tax_rule'] ?? [];
		}, 259200);

		foreach ($rules as $rate) {
			$this->taxRates[$rate['tax_type_id']][$rate['tax_rate_id']] = [
				'tax_rate_id' => $rate['tax_rate_id'],
				'name'        => $rate['name'],
				'rate'        => $rate['rate'],
				'type'        => $rate['type'],
				'priority'    => $rate['priority'],
				'based'       => $based,
			];
		}
	}

	public function addTaxes($value, $taxTypeId, $onlyTax = false) {
		$amount = 0;

		if (isset($this->taxRates[$taxTypeId])) {
			foreach ($this->taxRates[$taxTypeId] as $taxRate) {
				if ($taxRate['type'] == 'f') {//fixed
					$amount += $taxRate['rate'];
				} elseif ($taxRate['type'] == 'p') {//percent
					$amount += ($value / 100 * $taxRate['rate']);
				}
			}
		}

		if ($onlyTax) {
			return $amount;
		}

		return $value + $amount;
	}

	public function getRates($value, $taxTypeId) {
		$taxRate_data = [];

		if (isset($this->taxRates[$taxTypeId])) {
			foreach ($this->taxRates[$taxTypeId] as $taxRate) {
				if (isset($taxRate_data[$taxRate['tax_rate_id']])) {
					$amount = $taxRate_data[$taxRate['tax_rate_id']]['amount'];
				} else {
					$amount = 0;
				}

				if ($taxRate['type'] == 'f') {
					$amount += $taxRate['rate'];
				} elseif ($taxRate['type'] == 'p') {
					$amount += ($value / 100 * $taxRate['rate']);
				}

				$taxRate_data[$taxRate['tax_rate_id']] = [
					'tax_rate_id'  => $taxRate['tax_rate_id'],
					'name'         => $taxRate['name'],
					'rate'         => $taxRate['rate'],
					'type'         => $taxRate['type'],
					'amount'       => $amount,
				];
			}
		}

		return $taxRate_data;
	}

	public function clear() {
		$this->taxRates = [];
	}
}
