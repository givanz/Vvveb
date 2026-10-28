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

namespace Vvveb\Controller\Order;

use function Vvveb\__;
use Vvveb\Controller\Crud;
use Vvveb\Sql\CountrySQL;
use Vvveb\Sql\RegionSQL;
use Vvveb\Sql\WarehouseSQL;

class Warehouse extends Crud {
	protected string $type = 'warehouse';

	protected string $controller = 'warehouse';

	protected string $module = 'order';

	/**
	 * Save the warehouse and its assigned products.
	 *
	 * @return void
	 */
	public function save(): void {
		parent::save();

		$post         = &$this->request->post;
		$get          = &$this->request->get;
		$warehouse_id = $post['warehouse_id'] ?? $get['warehouse_id'] ?? $this->warehouse_id ?? false;
		$products     = $post['warehouse_product'] ?? [];

		if ($warehouse_id && $products) {
			$warehouseSQL = new WarehouseSQL();
			$warehouseSQL->setProducts(['warehouse_product' => $products, 'warehouse_id' => $warehouse_id]);
		}

		$this->index();
	}

	/**
	 * Display the warehouse edit form with countries, regions and products.
	 *
	 * @return void
	 */
	public function index(): void {
		parent::index();

		$get          = &$this->request->get;
		$warehouse_id = $get['warehouse_id'] ?? false;

		$warehouseSQL = new WarehouseSQL();
		$this->view->warehouse += $warehouseSQL->getProducts(['warehouse_id' => $warehouse_id] + $this->global) ?? [];

		$options = [
			'language_id' => $this->global['language_id'] ?? 1,
			'status'      => 1,
		];

		$countryModel          = new CountrySQL();
		$country               = $countryModel->getAll($options);
		$this->view->countries = $country['country'] ?? [];

		$country_id = $this->view->warehouse['country_id'] ?? ($this->view->countries[0]['country_id'] ?? 0);

		$regionModel         = new RegionSQL();
		$regions             = $regionModel->getAll($options + ['country_id' => $country_id]);
		$this->view->regions = $regions['region'] ?? [];

		$this->view->status = ['0' => __('Disabled'), '1' => __('Enabled')];
	}
}
