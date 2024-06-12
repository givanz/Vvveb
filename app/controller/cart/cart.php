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

use Vvveb\Controller\Base;
use Vvveb\System\Cart\Cart as ShoppingCart;
use Vvveb\System\Core\View;
use Vvveb\System\Payment;
use Vvveb\System\Shipping;

class Cart extends Base {
	use CouponTrait;

	function index() {
		$options = array_intersect_key($this->global['site'],
		array_flip(['weight_type_id', 'length_type_id', 'currency_id', 'country_id']));

		$cart     = ShoppingCart::getInstance($options);
		$payment  = Payment::getInstance();
		$shipping = Shipping::getInstance();

		$this->view->payment  = $payment->getMethods([]);
		$this->view->shipping = $shipping->getMethods([]);

		if (isset($this->request->post['product_id']) &&
			(isset($this->request->get['route']) && $this->request->get['route'] == 'cart/cart/add')) {
			$cart->add($this->request->post['product_id']);
		}

		$cart = [
			'products'        => $cart->getAll(),
			'totals'          => $cart->getTotals(),
			'total_items'     => $cart->getNoProducts(),
			'total_weight'    => $cart->getWeight(),
			'total_price'     => $cart->getNoProducts(),
			'total'           => $cart->getGrandTotal(),
			'coupons'         => $cart->getCoupons(),
			'total_formatted' => $cart->getGrandTotalFormatted(),
			'weight_unit' 	  => $this->global['site']['weight_type'],
			'length_unit' 	  => $this->global['site']['length_type']
		];

		$this->view->cart = $cart;
	}

	private function action($action, $productId = null, $quantity = 1) {
		$cart = ShoppingCart::getInstance($this->global);

		$productId          = $this->request->request['product_id'] ?? false;
		$key                = $this->request->request['key'] ?? false;
		$quantity           = $this->request->request['quantity'] ?? $quantity;
		$option             = $this->request->request['option'] ?? [];
		$subscriptionPlanId = $this->request->request['subscription_plan_id'] ?? false;

		if ($key || $productId) {
			//$this->view->success = false;
			switch ($action) {
				case 'add':
					$cart->add($productId, $quantity, $option, $subscriptionPlanId);

				break;

				case 'update':
					$cart->update($key, $quantity);

				break;

				case 'remove':
					$cart->remove($key);

				break;
			}
			//$this->view->success = $cart->$action($productId, $quantity);
		}

		$this->view->noJson = true;

		return $this->index();
	}

	function remove() {
		return $this->action('remove');
	}

	function update() {
		return $this->action('update');
	}

	function add() {
		return $this->action('add');
	}
}
