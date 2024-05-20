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

namespace Vvveb\Controller\User;

use Vvveb\Sql\User_wishlistSQL;

class Wishlist extends Base {
	function index() {
	}

	private function action($action) {
		$productId = (int) ($this->request->request['product_id'] ?? false);

		if ($productId) {
			$wishlist = new User_wishlistSQL();

			switch ($action) {
				case 'add':
				$result   = $wishlist->add(['user_id' => $this->global['user_id'], 'product_id' => $productId]);

				break;

				case 'remove':
				$result   = $wishlist->delete(['user_id' => $this->global['user_id'], 'product_id' => [$productId]]);

				break;
			}

			return $this->index();
		}
	}

	function add() {
		return $this->action('add');
	}

	function remove() {
		return $this->action('remove');
	}
}
