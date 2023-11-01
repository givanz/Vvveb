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

use Vvveb\Controller\Listing;
use Vvveb\Sql\countrySQL;

class Countries extends Listing {
	protected $type = 'country';

	protected $controller = 'country';

	protected $listController = 'countries';

	protected $list = 'country';

	protected $module = 'localization';

	function countryAutocomplete() {
		$country = new countrySQL();

		$options = [
			'start'  => 0,
			'limit'  => 10,
			'search' => trim($this->request->get['text']),
		] + $this->global;

		$results = $country->getAll($options);

		$search = [];

		foreach ($results['country'] as $country) {
			//$country['image']               = Images::image($country['image'], 'country');
			$search[$country['country_id']] = $country['name'];
		}

		//echo json_encode($search);
		$this->response->setType('json');
		$this->response->output($search);

		return false;
	}
}
