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

use Vvveb\Sql\CartSQL;

/**
 * Provides database persistence for the cart.
 *
 * @property int|string|null       $cart_id
 * @property string                $sessionKey
 * @property array<string, mixed>  $products
 * @property array<string, mixed>  $taxes
 * @property array<string, mixed>  $totals
 * @property int                   $total_items
 * @property bool                  $hasChanges
 * @property \Vvveb\System\Session $session
 */
trait DbTrait {
	private ?CartSQL $cartTable = null;

	/**
	 * Initialise the cart database table model.
	 */
	protected function initDb(): void {
		$this->cartTable = new CartSQL();
	}

	/**
	 * Read cart data from the database.
	 *
	 * @return array<string, mixed>
	 */
	protected function getDbCart(): array {
		$data = [];

		if (!$this->cartTable) {
			$this->initDb();
		}

		if ($cart = $this->cartTable->get(['cart_id' => $this->cart_id/*, 'user_id' => $this->userId*/])) {
			if (isset($cart['data']) && $cart['data']) {
				$data = json_decode($cart['data'], true);
			}
		}

		return $data;
	}

	/**
	 * Apply stored cart data to this instance.
	 *
	 * Only known cart properties are assigned, so unexpected keys in
	 * session or database rows cannot create dynamic properties.
	 *
	 * @param array<string, mixed> $data
	 */
	private function applyData(array $data): void {
		foreach (['products', 'taxes', 'totals', 'total_items', 'coupons', 'product_options', 'cart_id'] as $property) {
			if (array_key_exists($property, $data)) {
				$this->$property = $data[$property];
			}
		}
	}

	/**
	 * Load a cart from the database by cart ID.
	 *
	 * @param int|string|null $cart_id
	 *
	 * @return array<string, mixed>
	 */
	public function loadCart(int|string|null $cart_id): array {
		$data = [];

		if ($cart_id) {
			$this->cart_id = $cart_id;
			$data          = $this->getDbCart();
		}

		if ($data) {
			$this->applyData($data);

			$this->updateCart();
		}

		return $data;
	}

	/**
	 * Read cart data from session or database.
	 *
	 * @return array<string, mixed>
	 */
	protected function read(): array {
		$data          = $this->session->get($this->sessionKey) ?? [];
		$this->cart_id ??= $data['cart_id'] ?? null;

		if (!$data && $this->cart_id) {
			$data = $this->getDbCart();
		}

		if ($data) {
			$this->applyData($data);
		}

		return $data;
	}

	/**
	 * Persist current cart state to database and session.
	 */
	protected function write(): void {
		$data = [];

		foreach (['products', 'taxes', 'totals', 'total_items', 'coupons', 'product_options'] as $property) {
			$data[$property] = $this->$property;
		}

		$json = json_encode($data);

		$cart         = [];
		$cart['data'] = $json;

		if (!$this->cartTable) {
			$this->initDb();
		}

		if ($this->cart_id) {
			$this->cartTable->edit(['cart_id' => $this->cart_id, 'cart' => $cart]);
		} else {
			if ($return = $this->cartTable->add(['cart' => $cart])) {
				$this->cart_id = $return['cart'];
			}
		}

		$data['cart_id'] = $this->cart_id;

		$this->session->set($this->sessionKey, $data);

		$this->hasChanges = false;
	}

	/**
	 * Empty the cart from both session and database.
	 */
	public function empty(): bool {
		if (!$this->cartTable) {
			$this->initDb();
		}

		$this->session->set($this->sessionKey, []);

		if ($this->cart_id && $this->cartTable->delete(['cart_id' => [$this->cart_id]])) {
			$this->cart_id = null;

			return true;
		}

		return false;
	}
}
