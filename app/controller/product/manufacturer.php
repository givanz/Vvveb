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

use \Vvveb\Sql\ManufacturerSQL;
use function Vvveb\__;
use Vvveb\Controller\Base;

class Manufacturer extends Base {
	function index() {
		$slug                          = $this->request->get['slug'] ?? '';
		$this->view->manufacturer_name = $slug;

		if ($slug) {
			$manufacturerSql = new ManufacturerSQL();
			$options         = $this->global + ['slug' => $slug];
			$manufacturer    = $manufacturerSql->get($options);

			if ($manufacturer) {
				$this->request->request['manufacturer_id'] = $manufacturer['manufacturer_id'];
				$this->view->manufacturer                  = $manufacturer;
				$this->view->manufacturer_name             = $manufacturer['name'];
			} else {
				$message = __('Manufacturer not found!');
				$this->notFound(true, $message);
			}
		}
	}
}
