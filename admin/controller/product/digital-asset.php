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

use Vvveb\Controller\Crud;
use Vvveb\System\Images;

class DigitalAsset extends Crud {
	protected $type = 'digital_asset';

	protected $controller = 'digital-asset';

	protected $module = 'product';

	function index() {
		parent::index();

		if ($this->view->digital_asset) {
			$this->view->digital_asset['image']     = $this->view->digital_asset['file'];
			$this->view->digital_asset['image_url'] = Images::image($this->view->digital_asset['image'], 'digital_asset', 'thumb');
		}
	}
}
