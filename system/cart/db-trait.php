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

trait DbTrait {
	private $cartTable;

	protected function initDb() {
		$this->cartTable = new CartSQL();
	}

	protected function getDbCart() {
		$data = [];

		if (! $this->cartTable) {
			$this->initDb();
		}

		if ($cart = $this->cartTable->get(['cart_id' => $this->cart_id/*, 'user_id' => $this->userId*/])) {
			if (isset($cart['data']) && $cart['data']) {
				$data = json_decode($cart['data'], true);
			}
		}

		return $data;
	}

	function loadCart($cart_id) {
		$data = [];

		if ($cart_id) {
			$this->cart_id = $cart_id;
			$data          = $this->getDbCart();
		}

		if (is_array($data) && $data) {
			foreach ($data as $property => $value) {
				$this->$property = $value;
			}

			$this->updateCart();
		}

		return $data;
	}

	protected function read() {
		$data          = $this->session->get($this->sessionKey) ?? [];
		$this->cart_id = $this->cart_id ?? $data['cart_id'] ?? null;

		if (! $data && $this->cart_id) {
			$data = $this->getDbCart();
		}

		if (is_array($data) && $data) {
			foreach ($data as $property => $value) {
				$this->$property = $value;
			}
		}

		return $data;
	}

	protected function write() {
		$data = [];

		foreach (['products', 'taxes', 'totals',  'total_items', 'coupons', 'product_options'] as $property) {
			$data[$property] = $this->$property;
		}

		$json = json_encode($data);

		$cart         = [];
		$cart['data'] = $json;

		if (! $this->cartTable) {
			$this->initDb();
		}

		if ($this->cart_id) {
			if ($return = $this->cartTable->edit(['cart_id' => $this->cart_id, 'cart' => $cart])) {
			}
		} else {
			if ($return = $this->cartTable->add(['cart' => $cart])) {
				$this->cart_id = $return['cart'];
			}
		}

		$data['cart_id'] = $this->cart_id;

		$this->session->set($this->sessionKey, $data);
	}

	public function empty() {
		if (! $this->cartTable) {
			$this->initDb();
		}

		$this->session->set($this->sessionKey, []);

		if ($this->cart_id && $this->cartTable->delete(['cart_id' => [$this->cart_id]])) {
			$this->cart_id = null;

			return true;
		}
	}
}
