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

namespace Vvveb\Controller;

include DIR_ROOT . 'app' . DS . 'controller' . DS . 'cart' . DS . 'coupon-trait.php';

use Vvveb\System\Cart\Cart as ShoppingCart;
use Vvveb\System\Payment;
use Vvveb\System\Shipping;

class Cart extends Base {
	use Cart\CouponTrait;

	/** @var ShoppingCart|null */
	private ?ShoppingCart $cart = null;

	/**
	 * Initialize the shopping cart instance.
	 *
	 * @param int|null $cart_id
	 *
	 * @return void
	 */
	private bool $initialized = false;

	private function cart(?int $cart_id = null): void {
		//GraphQL resolvers bypass FrontController, so init() runs on first use;
		//guard it to avoid re-running auth/plugins/site setup for every field
		if (!$this->initialized) {
			$this->init();
			$this->initialized = true;
		}

		$options = array_intersect_key(
			$this->global['site'],
			array_flip(['weight_type_id', 'length_type_id', 'currency_id', 'country_id'])
		);

		$options['cart_id'] = $cart_id;

		$this->cart = ShoppingCart::getInstance($this->global + $options);
	}

	/**
	 * Retrieve the full cart contents and totals.
	 *
	 * @param array<string, mixed> $args
	 *
	 * @return array<string, mixed>
	 */
	public function index(array $args = []): array {
		$cart_id = $args['cart_id'] ?? null;

		if (!$this->cart) {
			$this->cart($cart_id);
		}

		$payment      = Payment::getInstance();
		$shipping     = Shipping::getInstance();
		$paymentInfo  = [];
		$shippingInfo = [];

		$payment->getMethods($paymentInfo);
		$shipping->getMethods($shippingInfo);

		return [
			'cart_id'           => $this->cart->getId(),
			'encrypted_cart_id' => $this->cart->getEncryptedId(),
			'products'          => $this->cart->getAll(),
			'totals'            => $this->cart->getTotals(),
			'total_items'       => $this->cart->getNoProducts(),
			'total_weight'      => $this->cart->getWeight(),
			'total_price'       => $this->cart->getGrandTotal(),
			'total'             => $this->cart->getGrandTotal(),
			'total_tax'         => $this->cart->getTaxTotal(),
			'coupons'           => $this->cart->getCoupons(),
			'total_formatted'   => $this->cart->getGrandTotalFormatted(),
			'checkout_url'      => $this->cart->getCheckoutUrl(),
			'weight_unit'       => $this->global['site']['weight_type'],
			'length_unit'       => $this->global['site']['length_type'],
		];
	}

	/**
	 * Add a product to the cart.
	 *
	 * @param array<string, mixed> $args
	 *
	 * @return array<string, mixed>|null
	 */
	public function add(array $args): ?array {
		$cart_id = $args['cart_id'] ?? null;

		if (!$this->cart) {
			$this->cart($cart_id);
		}

		$productId          = $args['product_id'] ?? false;
		$quantity           = $args['quantity'] ?? 1;
		$options            = $args['options'] ?? [];
		$subscriptionPlanId = $args['subscription_plan_id'] ?? false;
		$productVariantId   = $args['product_variant_id'] ?? false;

		if ($productId) {
			if ($options && is_string($options)) {
				if ($options[0] !== '{') {
					$options = '{' . $options . '}';
				}
				$options = json_decode($options, true);
			}
			$this->cart->add($productId, $quantity, $options, $productVariantId, $subscriptionPlanId);

			return $this->index($args);
		}

		return null;
	}

	/**
	 * Create a new empty cart.
	 *
	 * @param array<string, mixed> $args
	 *
	 * @return array<string, mixed>
	 */
	public function create(array $args): array {
		if (!$this->cart) {
			$this->cart();
			$args['cart_id'] = $this->cart->create();
		}

		return $this->index($args);
	}

	/**
	 * Update the quantity of a cart item.
	 *
	 * @param array<string, mixed> $args
	 *
	 * @return array<string, mixed>|null
	 */
	public function update(array $args): ?array {
		$cart_id = $args['cart_id'] ?? null;

		if (!$this->cart) {
			$this->cart($cart_id);
		}

		if (($key = ($args['key'] ?? $args['product_id'] ?? false)) && ($quantity = ($args['quantity'] ?? false))) {
			$this->cart->update($key, $quantity);

			return $this->index($args);
		}

		return null;
	}

	/**
	 * Remove an item from the cart.
	 *
	 * @param array<string, mixed> $args
	 *
	 * @return array<string, mixed>|null
	 */
	public function remove(array $args): ?array {
		$cart_id = $args['cart_id'] ?? null;

		if (!$this->cart) {
			$this->cart($cart_id);
		}

		if ($key = ($args['key'] ?? $args['product_id'] ?? false)) {
			$this->cart->remove($key);

			return $this->index($args);
		}

		return null;
	}
}
