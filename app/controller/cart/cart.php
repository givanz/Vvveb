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
use Vvveb\System\Event;

class Cart extends Base {
	use CartTrait;
	use CouponTrait;

	/** @var ShoppingCart|null Shopping cart instance. */
	private ?ShoppingCart $cart = null;

	/**
	 * Initialise the cart from session/request data.
	 *
	 * @return string|null
	 */
	public function init(): ?string {
		parent::init();

		$options = array_intersect_key(
			$this->global['site'],
			array_flip(['weight_type_id', 'length_type_id', 'currency_id', 'country_id'])
		);

		$encrypted_cart_id = false;

		if (isset($this->request->get['cart_id'])) {
			$encrypted_cart_id = $this->request->get['cart_id'];
		}

		$this->cart = ShoppingCart::getInstance($this->global + $options);

		if ($encrypted_cart_id) {
			if ($cart_id = $this->cart->setEncryptedId($encrypted_cart_id)) {
				$this->cart->loadCart($cart_id);
			}
		}

		return null;
	}

	/**
	 * Display the shopping cart.
	 *
	 * @return void
	 */
	public function index(): void {
		$checkoutInfo         = $this->session->get('checkout') ?? ['user' => $this->global['user']];

		$product_id = $this->request->post['product_id'] ?? $this->request->get['product_id'] ?? false;
		$module     = $this->request->get['module'] ?? $this->request->post['module'] ?? '';

		if ($product_id && ($module == 'cart/cart/add' || $module == 'cart/cart')) {
			$this->action('add', $product_id);
		}

		$hasShipping             = $this->cart->hasShipping();
		$subscription            = $this->cart->getSubscription();
		$hasPayment              = ($this->cart->getGrandTotal() > 0);

		$this->shippingPayment($checkoutInfo, $hasShipping, $hasPayment);

		$cart = [
			'products'          => $this->cart->getAll(),
			'totals'            => $this->cart->getAllTotals(),
			'total_items'       => $this->cart->getNoProducts(),
			'total_weight'      => $this->cart->getWeight(),
			'subtotal'          => $this->cart->getSubTotal(),
			'total'             => $this->cart->getGrandTotal(),
			'coupons'           => $this->cart->getCoupons(),
			'total_formatted'   => $this->cart->getGrandTotalFormatted(),
			'cart_id'           => ($cartId = $this->cart->getId()),
			'encrypted_cart_id' => $cartId ? $this->cart->getEncryptedId() : '',
			'weight_unit'       => $this->global['site']['weight_type'],
			'length_unit'       => $this->global['site']['length_type'],
		];

		$this->view->cart = $cart;
	}

	/**
	 * Perform a cart action: add, update, or remove.
	 *
	 * @param string          $action
	 * @param int|string|null $productId
	 * @param int             $quantity
	 *
	 * @return void
	 */
	private function action(string $action, int|string|null $productId = null, int $quantity = 1): void {
		$productId          = $this->request->request['product_id'] ?? $productId ?? false;
		$key                = $this->request->request['key'] ?? false;
		$quantity           = $this->request->request['quantity'] ?? $quantity;
		$option             = $this->request->request['option'] ?? [];
		$subscriptionPlanId = $this->request->request['subscription_plan_id'] ?? false;
		$productVariantId   = $this->request->request['product_variant_id'] ?? false;

		[$action, $productId, $key, $quantity, $option, $subscriptionPlanId] =
		Event::trigger(__CLASS__, __FUNCTION__, $action, $productId, $key, $quantity, $option, $subscriptionPlanId);

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
		}

		$this->view->noJson = true;
	}

	/**
	 * Remove an item from the cart and redisplay.
	 *
	 * @return void
	 */
	public function remove(): void {
		$this->action('remove');

		$this->index();
	}

	/**
	 * Update a cart item quantity and redisplay.
	 *
	 * @return void
	 */
	public function update(): void {
		$this->action('update');

		$this->index();
	}

	/**
	 * Add an item to the cart and redisplay.
	 *
	 * @return void
	 */
	public function add(): void {
		$this->index();
	}
}
