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

namespace Vvveb\Controller\Localization;

use function Vvveb\__;
use Vvveb\Controller\Crud;
use Vvveb\Sql\CountrySQL;

class Region extends Crud {
	protected $type = 'region';

	protected $controller = 'region';

	protected $module = 'localization';

	function index() {
		parent::index();

		$countryModel  = new CountrySQL();
		$options       = $this->global;
		unset($options['limit']);
		$country	 = $countryModel->getAll($options);

		$this->view->countries = $country['country'] ?? [];
		$this->view->status    = [0 => __('Inactive'), 1 => __('Active')];
	}
}
