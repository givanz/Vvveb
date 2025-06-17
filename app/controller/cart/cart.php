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
use Vvveb\System\Event;
use Vvveb\System\Payment;
use Vvveb\System\Shipping;
//use Vvveb\Trait\Cart as CartTrait;

class Cart extends Base {
//	use CartTrait;
	use CouponTrait;

	private $cart;

	function init() {
		parent::init();

		$options = array_intersect_key($this->global['site'],
		array_flip(['weight_type_id', 'length_type_id', 'currency_id', 'country_id']));

		$cart_id = false;

		if (isset($this->request->get['cart_id'])) {
			$cart_id = $options['cart_id'] = $this->request->get['cart_id'];
		}

		$this->cart = ShoppingCart::getInstance($this->global + $options);

		if ($cart_id) {
			$this->cart->loadCart($cart_id);
		}
	}

	function index() {
		$payment    = Payment::getInstance();
		$shipping   = Shipping::getInstance();

		$this->view->payment  = $payment->getMethods([]);
		$this->view->shipping = $shipping->getMethods([]);

		$product_id = $this->request->post['product_id'] ?? $this->request->get['product_id'] ?? false;
		$module     = $this->request->get['module'] ?? $this->request->post['module'] ?? '';

		if ($product_id && ($module == 'cart/cart/add' || $module == 'cart/cart')) {
			$this->action('add', $product_id);
		}

		$cart = [
			'products'          => $this->cart->getAll(),
			'totals'            => $this->cart->getTotals(),
			'total_items'       => $this->cart->getNoProducts(),
			'total_weight'      => $this->cart->getWeight(),
			'total_price'       => $this->cart->getNoProducts(),
			'total'             => $this->cart->getGrandTotal(),
			'coupons'           => $this->cart->getCoupons(),
			'total_formatted'   => $this->cart->getGrandTotalFormatted(),
			'cart_id'           => ($cardId = $this->cart->getId()),
			'encrypted_cart_id' => $cardId ? $this->cart->getId() : '',
			'weight_unit'       => $this->global['site']['weight_type'],
			'length_unit'       => $this->global['site']['length_type'],
		];

		$this->view->cart = $cart;
	}

	private function action($action, $productId = null, $quantity = 1) {
		$productId          = $this->request->request['product_id'] ?? $productId ?? false;
		$key                = $this->request->request['key'] ?? false;
		$quantity           = $this->request->request['quantity'] ?? $quantity;
		$option             = $this->request->request['option'] ?? [];
		$subscriptionPlanId = $this->request->request['subscription_plan_id'] ?? false;
		$productVariantId   = $this->request->request['product_variant_id'] ?? false;

		list($action, $productId, $key, $quantity, $option, $subscriptionPlanId) =
		Event :: trigger(__CLASS__,__FUNCTION__, $action, $productId, $key, $quantity, $option, $subscriptionPlanId);

		if ($key || $productId) {
			switch ($action) {
				case 'add':
					$this->cart->add($productId, $quantity, $option, $productVariantId, $subscriptionPlanId);

				break;

				case 'update':
					$this->cart->update($key, $quantity);

				break;

				case 'remove':
					$this->cart->remove($key);

				break;
			}
			//$this->view->success = $this->cart->$action($productId, $quantity);
		}

		$this->view->noJson = true;
	}

	function remove() {
		$this->action('remove');

		return $this->index();
	}

	function update() {
		$this->action('update');

		return $this->index();
	}

	function add() {
		return $this->index();
	}
}
