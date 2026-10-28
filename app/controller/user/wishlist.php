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

use function Vvveb\__;
use Vvveb\Sql\User_wishlistSQL;

class Wishlist extends Base {
	/**
	 * Display the wishlist page.
	 *
	 * @return void
	 */
	public function index(): void {}

	/**
	 * Perform a wishlist action: add or remove a product.
	 *
	 * @param string $action
	 *
	 * @return void
	 */
	private function action(string $action): void {
		$productId = (int)($this->request->post['product_id'] ?? false);
		$userId    = (int)($this->global['user_id'] ?? 0);

		if (!$userId) {
			$this->view->errors[] = __('You need to be logged in to use the wishlist!');

			return;
		}

		if ($productId) {
			$wishlist = new User_wishlistSQL();

			switch ($action) {
				case 'add':
					$result   = $wishlist->add(['user_id' => $userId, 'product_id' => $productId]);

					break;

				case 'remove':
					$result   = $wishlist->delete(['user_id' => $userId, 'product_id' => [$productId]]);

					break;
			}

			$this->index();
		}
	}

	/**
	 * Add a product to the wishlist.
	 *
	 * @return void
	 */
	public function add(): void {
		$this->action('add');
	}

	/**
	 * Remove a product from the wishlist.
	 *
	 * @return void
	 */
	public function remove(): void {
		$this->action('remove');
	}
}
