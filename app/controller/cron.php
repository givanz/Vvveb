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

use Vvveb\System\Core\Request;

/**
 * Cron job controller — invoked via HTTP with a secret key or from CLI.
 */
#[\AllowDynamicProperties]
class Cron {
	/** @var Request Request instance injected by FrontController DI. */
	public Request $request;

	/**
	 * Entry point for cron execution. Validates the cron key for HTTP requests
	 * then runs all registered cron jobs.
	 *
	 * @return void
	 */
	public function index(): void {
		if (!defined('CLI')) {
			$key     = $this->request->get['key'] ?? '';
			$cronkey = \Vvveb\getConfig('app.cronkey');

			if ($key != $cronkey) {
				exit('Invalid key!');
			}
		}

		echo 'Run cron';
		ignore_user_abort(true);

		if (function_exists('fastcgi_finish_request')) {
			fastcgi_finish_request();
		} elseif (function_exists('litespeed_finish_request')) {
			litespeed_finish_request();
		}

		$results = \Vvveb\System\Cron::runAll();

		//single background-queue pass for hosts without a persistent worker
		try {
			$results['queue'] = \Vvveb\System\Queue::cronPass();
		} catch (\Throwable $e) {
			\Vvveb\logError('[Queue] Cron pass failed: ' . $e->getMessage());
		}

		if (defined('CLI')) {
			foreach ($results as $name => $result) {
				echo $name . ': ' . json_encode($result) . "\n";
			}
		}

		exit();
	}
}
