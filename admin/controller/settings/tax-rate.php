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

namespace Vvveb\Controller\Settings;

use function Vvveb\__;
use Vvveb\Controller\Crud;
use Vvveb\Sql\Region_GroupSQL;

class TaxRate extends Crud {
	protected $type = 'tax_rate';

	protected $controller = 'tax-rate';

	protected $module = 'settings';

	function index() {
		parent::index();

		$geoRegion = new Region_GroupSQL();
		$regions	  = $geoRegion->getAll();

		$region_group_id = [];

		foreach ($regions['region_group'] as $region_group) {
			$region_group_id[$region_group['region_group_id']] = $region_group['name'];
		}

		$this->view->type            = ['f' => __('Fixed'), 'p' => __('Percent')];
		$this->view->region_group_id = $region_group_id;
	}
}
