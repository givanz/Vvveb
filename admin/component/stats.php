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

use Vvveb\Sql\StatSQL;
use Vvveb\System\Component\ComponentBase;
use Vvveb\System\Event;

class Stats extends ComponentBase {
	public static $defaultOptions = [
		'start'       => 0,
		'limit'       => 10,
		'language_id' => null,
		'site_id'     => null,
		'stat'        => ['url', 'price asc'],
		'start_date'  => '',
		'end_date'    => 'NOW()',
		'range'       => 'day', //day, week, month, year
	];

	public $options = [];

	function results() {
		$stats = new StatSQL();

		if ($this->options['end_date'] == 'NOW()') {
			$this->options['end_date'] = date('Y-m-d');
		}

		if ($this->options['start_date'] == 'NOW()') {
			$this->options['end_date'] = date('Y-m-d');
		}

		$results = $stats->getStats($this->options);
		$data    = [];

		foreach ($results['orders'] as $order) {
			$data[$order['date']]['orders'] = $order['orders'];
		}

		foreach ($results['users'] as $order) {
			$data[$order['date']]['users'] = $order['users'];
		}

		ksort($data);
		$labels = array_keys($data);

		$users  = [];
		$orders = [];

		foreach ($data as $date => $stat) {
			$users[]  = $stat['users'] ?? 0;
			$orders[] = $stat['orders'] ?? 0;
		}

		list($labels, $users, $orders) = Event :: trigger(__CLASS__,__FUNCTION__, $labels, $users, $orders);

		return ['labels' => $labels, 'users' => $users, 'orders' => $orders];

		return $results;
	}
}
