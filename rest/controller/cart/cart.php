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

namespace Vvveb\Controller\Cart;

include DIR_ROOT . 'app' . DS . 'controller' . DS . 'cart' . DS . 'coupon-trait.php';

include DIR_ROOT . 'app' . DS . 'controller' . DS . 'cart' . DS . 'cart.php';

class CartBase extends Cart {
	function index() {
		echo 'asd';

		die('asd');
	}

	function delete() {
		return $this->action('remove');
	}

	function put() {
		return $this->action('update');
	}

	function post() {
		return $this->action('add');
	}
}
