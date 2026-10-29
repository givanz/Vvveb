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

use Vvveb\Controller\Listing;
use Vvveb\System\User\Admin;

class DigitalAssets extends Listing {
	/** @var list<string> */
	protected ?array $additionalPermissionCheck = ['product/digital-asset/save'];

	protected string $type = 'digital_asset';

	protected string $controller = 'digital-asset';

	protected string $listController = 'digital-assets';

	protected string $list = 'digital_asset';

	protected string $module = 'product';

	/**
	 * Delete digital assets, checking edit capabilities first.
	 *
	 * @return void
	 */
	public function delete(): void {
		$editCapability = 'edit_other_products';

		if (Admin::hasCapability($editCapability)) {
			$this->adminFilter = false;
		}

		parent::delete();
	}

	/**
	 * Display the digital assets listing, checking view capabilities first.
	 *
	 * @return void
	 */
	public function index(): void {
		$editCapability = 'view_other_products';

		if (Admin::hasCapability($editCapability)) {
			$this->adminFilter = false;
		}

		parent::index();
	}
}
