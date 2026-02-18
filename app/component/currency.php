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
use function Vvveb\session;
use Vvveb\System\Component\ComponentBase;
use Vvveb\System\Event;

class Currency extends ComponentBase {
	public static $defaultOptions = [
		'start'   => 1,
		'limit'   => 1000,
		'status'  => 1,
		'site_only' => true, //show only site available currencies otherwise show all active
		'default' => null,
	];

	function cacheKey() {
		//disable caching
		return false;
	}

	function results() {
		$results             = [];
		$results['active']   = false;
		$results['current']  = self :: $global['currency'];
		$results['currency'] = availableCurrencies();

		if (isset($results['currency']) && $results['currency']) {
			if (isset($this->options['site_only']) && $this->options['site_only'] && self :: $global['currencies']) {
				$results['currency'] = array_intersect_key($results['currency'], self :: $global['currencies']);
			}

			$code     = session('currency') ?? self :: $global['currency'];

			if ($code && isset($results['currency'][$code])) {
				$currency = $results['currency'][$code] ?? [];
				$results['current']    = $code;
				$results['active']     = $currency;
			}
		}

		list($results) = Event :: trigger(__CLASS__,__FUNCTION__, $results);

		return $results;
	}
}
