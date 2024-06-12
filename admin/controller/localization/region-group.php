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
use Vvveb\Sql\Region_GroupSQL;
use Vvveb\Sql\regionSQL;

class RegionGroup extends Crud {
	protected $type = 'region_group';

	protected $controller = 'region-group';

	protected $module = 'localization';

	function save() {
		$region          = $this->request->post['region'] ?? [];
		$region_group_id = $this->request->get['region_group_id'] ?? false;

		if ($region_group_id) {
			$regionGroup = new Region_GroupSQL();
			$result      = $regionGroup->addRegions(['region_group_id' => $region_group_id, 'region_to_region_group' => $region]);

			if ($result && isset($result['region_to_region_group'])) {
				//$successMessage        = __('Region saved!');
				//$this->view->success[] = $successMessage;
				$this->view->errors    = [];
			} else {
				$this->view->errors[] = __('Error saving!');
			}
		}

		parent::save();
	}

	function regions() {
		$country_id   = $this->request->get['country_id'] ?? false;
		$regions      = [];

		if ($country_id) {
			$region                  = new RegionSQL();
			$options                 = $this->global;
			$options['status'] 	     = 1;
			$options['country_id']   = $country_id;
			unset($options['limit']);
			$regions	               = $region->getAll($options)['region'] ?? [];
		}

		$this->response->setType('json');
		$this->response->output($regions);
		//return [];
	}

	function index() {
		parent::index();
		$region_group_id = $this->request->get['region_group_id'] ?? false;

		$regions = [];

		if ($region_group_id) {
			$regionGroup  = new Region_GroupSQL();
			$regions	     = $regionGroup->getRegions(['region_group_id' => $region_group_id])['regions'] ?? [];
		}
		$this->view->regions         = $regions;
		$this->view->region_group_id = $region_group_id;

		$countryModel      = new CountrySQL();
		$options           = $this->global;
		$options['status'] = 1;
		unset($options['limit']);
		$country	 = $countryModel->getAll($options);

		$this->view->countries = $country['country'] ?? [];

		$admin_path               = \Vvveb\adminPath();
		$controllerPath           = $admin_path . 'index.php?module=localization/region-group';
		$this->view->regionsUrl   = "$controllerPath&action=regions";
	}
}
