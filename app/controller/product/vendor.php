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

namespace Vvveb\Controller\Product;

use \Vvveb\Sql\VendorSQL;
use function Vvveb\__;
use Vvveb\Controller\Base;

class Vendor extends Base {
	function index() {
		$slug                    = $this->request->get['slug'] ?? '';
		$this->view->vendor_name = $slug;

		if ($slug) {
			$vendorSql   = new VendorSQL();
			$options     = $this->global + ['slug' => $slug];
			$vendor      = $vendorSql->get($options);

			if ($vendor) {
				$this->request->request['vendor_id'] = $vendor['vendor_id'];
				$this->view->vendor                  = $vendor;
				$this->view->vendor_name             = $vendor['name'];
			} else {
				$message = __('Vendor not found!');
				$this->notFound(true, $message);
			}
		}
	}
}
