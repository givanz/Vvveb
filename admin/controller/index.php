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

namespace Vvveb\Controller;

use Vvveb\Sql\StatSQL;

class Index extends Base {
	function index() {
	}

	function stats() {
		$options = [];
		$stats   = new StatSQL();

		if (isset($this->request->get['end_date'])) {
			$options['end_date'] = $this->request->get['end_date'];
		} else {
			$options['end_date'] = date('Y-m-d');
		}

		if (isset($this->request->get['start_date'])) {
			$options['start_date'] = $this->request->get['start_date'];
		} else {
			$options['start_date'] = date('Y-m-d');
		}

		$users  = filter_var(($this->request->get['users'] ?? 'false'), FILTER_VALIDATE_BOOLEAN);
		$orders = filter_var(($this->request->get['sales'] ?? 'false'), FILTER_VALIDATE_BOOLEAN);

		$results = $stats->getStats($options + $this->global);
		$data    = [];

		foreach ($results['orders'] as $order) {
			$data[$order['date']]['orders'] = $order['orders'];
		}

		foreach ($results['users'] as $order) {
			$data[$order['date']]['users'] = $order['users'];
		}

		ksort($data);
		$labels = array_keys($data);

		$usersData  = [];
		$ordersData = [];

		foreach ($data as $date => $stat) {
			$usersData[]  = $stat['users'] ?? 0;
			$ordersData[] = $stat['orders'] ?? 0;
		}

		$output = ['labels' => $labels];

		if ($users) {
			$output['users'] = $usersData;
		}

		if ($orders) {
			$output['orders'] = $ordersData;
		}

		$this->response->setType('json');
		$this->response->output($output);
	}

	function heartbeat() {
		die('ok');
	}
}
