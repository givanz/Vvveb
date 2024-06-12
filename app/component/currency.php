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

namespace Vvveb\Component;

use function Vvveb\availableCurrencies;
use Vvveb\System\Component\ComponentBase;
use Vvveb\System\Event;
use Vvveb\System\Session;

class Currency extends ComponentBase {
	public static $defaultOptions = [
		'status' => 1,
	];

	function cacheKey() {
		//disable caching
		return false;
	}

	function results() {
		$results             = [];
		$results['active']   = false;
		$results['current']  = false;
		$results['currency'] = availableCurrencies();

		if (isset($results['currency']) && $results['currency']) {
			$code     = Session::getInstance()->get('currency');
			$currency = $results['currency'][$code] ?? [];

			if ($currency) {
				$results['current']    = $code;
				$results['active']     = ['name' => $currency['name'], 'code' => $currency['code'], 'id' => $currency['currency_id']];
			}
		}

		list($results) = Event :: trigger(__CLASS__,__FUNCTION__, $results);

		return $results;
	}
}
