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

namespace Vvveb\Controller\User;

use function Vvveb\__;
use function Vvveb\getConfig;
use Vvveb\Sql\Digital_asset_logSQL;
use Vvveb\Sql\Digital_assetSQL;

class Downloads extends Base {
	function index() {
	}

	function download() {
		$public           = $this->request->get['public'] ?? false;
		$digital_asset_id = $this->request->get['digital_asset_id'] ?? false;
		$userKey          = $this->request->get['key'] ?? false;
		$customerOrderId  = $this->request->get['customer_order_id'] ?? false;
		$key              = getConfig('app.key');

		if ($digital_asset_id && $public && ($key == $userKey)) {
			$digital_asset = new Digital_assetSQL();

			$results = $digital_asset->get(
			[
				'digital_asset_id' => $digital_asset_id,
				'language_id'      => $this->global['language_id'],
				'user_id'          => $this->global['user_id'],
				'order_status_id'  => 4,
			]);

			if ($results && isset($results['file']) && ($customerOrderId == $results['customer_order_id'])) {
				$digital_asset_log = new Digital_asset_logSQL();
				$digital_asset_log->add([
					'digital_asset_log' => [
						'digital_asset_id' => $digital_asset_id,
						'user_id'          => $this->global['user_id'],
						'ip'               => $_SERVER['REMOTE_ADDR'] ?? '',
						'site_id'          => $this->global['site_id'],
					],
				]);

				$filename = trim($results['file'], '/');
				$public   = $results['public'];
				$file     = DIR_STORAGE . 'digital_assets' . DS . $filename;

				header('Content-Description: File Transfer');
				header('Content-Type: application/octet-stream');
				header('Content-Disposition: attachment; filename="' . $public . '"');
				header('Expires: 0');
				header('Cache-Control: must-revalidate');
				header('Pragma: public');
				header('Content-Length: ' . filesize($file));

				readfile($file);
			} else {
				die(__('Invalid download!'));
			}
		} else {
			die(__('Invalid download!'));
		}

		die(0);
	}
}
