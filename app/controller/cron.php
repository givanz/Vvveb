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

use function Vvveb\url;

#[\AllowDynamicProperties]
class Cron {
	function _construct() {
	}

	function index() {
		if (defined('CLI')) {
		} else {
			$key     = $this->request->get['key'];
			$cronkey = \Vvveb\get_config('app.cronkey');

			if ($key != $cronkey) {
				die('Invalid key!');
			}
		}

		echo 'Run cron';
		ignore_user_abort(true);

		if (function_exists('fastcgi_finish_request')) {
			fastcgi_finish_request();
		} elseif (function_exists('litespeed_finish_request')) {
			litespeed_finish_request();
		}

		if (! empty($this->request->post) || defined('CLI')) {
			die();
		}

		die();
	}

	function call() {
		$cronkey  = \Vvveb\get_config('app.cronkey');
		echo $url = url('cron/index', ['key' => $cronkey]);
		$options  = [
			'timeout'   => 0.01,
			'blocking'  => false,
			'sslverify' => false,
		];
	}
}
