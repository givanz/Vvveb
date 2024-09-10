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

use function Vvveb\getConfig;
use Vvveb\Sql\Digital_assetSQL;
use Vvveb\System\Component\ComponentBase;
use Vvveb\System\Event;
use function Vvveb\url;

class Digital_assets extends ComponentBase {
	public static $defaultOptions = [
		'start'           => 0,
		'user_id'         => null,
		'product_id'      => null,
		'order_status_id' => null,
		'limit'           => ['url', 4],
		'digital_asset'   => ['url', 'price asc'],
	];

	public $options = [];

	function results() {
		$digital_asset = new Digital_assetSQL();

		$results = $digital_asset->getAll($this->options);
		$key     = getConfig('app.key');

		if (isset($results['digital_asset'])) {
			foreach ($results['digital_asset'] as $id => &$digital_asset) {
				$digital_asset['url'] = url('user/downloads/download',[
					'digital_asset_id'  => $digital_asset['digital_asset_id'],
					'public'            => str_replace(['/', '-'], '_', $digital_asset['public']),
					'customer_order_id' => $digital_asset['customer_order_id'],
					'key'               => $key,
				]);
			}
		}

		list($results) = Event :: trigger(__CLASS__,__FUNCTION__, $results);

		return $results;
	}
}
