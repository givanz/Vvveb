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

namespace Vvveb\System\Cart;

use Vvveb\Sql\OrderSQL;

class Order {
	private $model;

	public static function getInstance($options = []) {
		static $inst = null;

		if ($inst === null) {
			$inst = new Order($options);
		}

		return $inst;
	}

	public function __construct() {
		$this->model = new OrderSQL();
	}

	public function getData() {
		return $this->model->getData();
	}

	public function add($data) {
		//check if email is already registerd
		return $this->model->add(['order' => $data]);
	}
}
