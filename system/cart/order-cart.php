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
use Vvveb\System\Session;

class OrderCart extends Cart {
	/** @var int|string|null */
	protected int|string|null $order_id = null;

	protected bool $controlCache = false;

	/**
	 * @param int|string           $order_id
	 * @param array<string, mixed> $options
	 */
	public function __construct(int|string $order_id, array $options = []) {
		$this->session               = Session::getInstance();
		$this->currency              = Currency::getInstance($options);
		$this->tax                   = Tax::getInstance();
		$this->tax->pricesIncludeTax = !empty($options['prices_include_tax']);
		$this->weight                = Weight::getInstance();

		$this->tax->setRegionRules($options['country_id'], $options['region_id'], $options['based'] ?? 'store', $options['user_group_id'] ?? null);

		$this->order_id = $order_id;
		$this->cart_id  = $options['cart_id'] ?? null;
		$this->options  = $options;

		if ($this->cart_id && !is_numeric($this->cart_id)) {
			$cart_id = urldecode($this->cart_id);
			$key     = \Vvveb\getConfig('app.key');
			$cart_id = \Vvveb\decrypt($key, $cart_id);

			if ($cart_id) {
				$this->encrypted_cart_id = $this->cart_id;
				$this->cart_id           = $cart_id;
			}
		}

		$this->read();
	}

	/**
	 * Add a product from an existing order into the cart.
	 *
	 * @param int|string                                                                            $productId
	 * @param int|string|false                                                                      $orderProductId
	 * @param int|float                                                                             $quantity
	 * @param array<int, array{product_option_id: int|string, product_option_value_id: int|string}> $option
	 * @param int|string|false                                                                      $subscriptionPlanId
	 */
	public function addOrderProduct(int|string $productId, int|string|false $orderProductId = false, int|float $quantity = 1, array $option = [], int|string|false $subscriptionPlanId = false): void {
		if (!$productId) {
			return;
		}

		$key = $productId;

		if ($option) {
			$optionKey = [];

			foreach ($option as $opt) {
				$optionKey[$opt['product_option_id']] = $opt['product_option_value_id'];
			}
			$key .= '_' . json_encode($optionKey);
		}

		if ($subscriptionPlanId) {
			$key .= "_$subscriptionPlanId";
		}

		$key = str_replace('"', '', $key);

		if (isset($this->products[$key])) {
			$this->products[$key]['quantity'] += $quantity;
		} else {
			$this->products[$key] = [
				'product_id'           => $productId,
				'order_product_id'     => $orderProductId,
				'quantity'             => $quantity,
				'option'               => $option,
				'subscription_plan_id' => $subscriptionPlanId,
			];
		}
	}

	/**
	 * Add or update a total entry for the order cart.
	 *
	 * @param string           $namespace
	 * @param string           $key
	 * @param string           $title
	 * @param float|int|string $value
	 * @param string           $text
	 * @param int|string|false $order_total_id
	 */
	public function addOrderTotal(string $namespace, string $key, string $title, float|int|string $value, string $text = '', int|string|false $order_total_id = false): void {
		$data = ['namespace' => $namespace, 'key' => $key, 'title' => $title, 'value' => $value, 'value_formatted' => $this->currency->format($value), 'text' => $text, 'order_total_id' => $order_total_id];

		if (isset($this->totals[$key])) {
			$this->totals[$key]['value']           = $value;
			$this->totals[$key]['value_formatted'] = $this->currency->format($value);
		} else {
			$this->totals[$key] = $data;
		}
		//$this->write();
	}

	/**
	 * Load order data from the database and populate the cart.
	 *
	 * @return array<string, mixed>
	 */
	protected function read(): array {
		$options = ['order_id' => (int)$this->order_id] + $this->options;

		$orders  = new OrderSQL();
		$results = $orders->get($options);

		if (isset($results['total']) && $results['total']) {
			foreach ($results['total'] as $total) {
				$this->addOrderTotal($total['namespace'] ?? 'total', $total['key'], $total['title'], $total['value'], $total['title'], $total['order_total_id']);
			}
		}
		if (isset($results['products']) && $results['products']) {
			foreach ($results['products'] as $product) {
				$option_value = $product['option_value'] ? json_decode($product['option_value'], true) : [];

				if (!is_array($option_value)) {
					$option_value = [];
				}

				$this->addOrderProduct($product['product_id'], $product['order_product_id'], $product['quantity'], $option_value);
			}
		}

		$this->updateCart();

		return $results;
	}

	/**
	 * Persist the order cart state back to the database.
	 */
	public function write(): void {
		$this->addTaxTotal();
		$order_id = $this->order_id;
		$orders   = new OrderSQL();

		$data   = ['total' => $this->getGrandTotal()];
		$result = $orders->edit(['order' => $data, 'order_id' => $order_id]);

		foreach ($this->products as $key => $product) {
			$product_options = $product['option'];

			if (isset($product['order_product_id']) && $product['order_product_id']) {
				$orders->editProduct(['product' => $product, 'product_options' => $product_options, 'order_product_id' => $product['order_product_id'], 'order_id' => $order_id]);
			} else {
				unset($product['order_product_id']);
				$orders->addProduct(['product' => $product, 'product_options' => $product_options, 'order_id' => $order_id]);
			}
		}

		foreach ($this->totals as $key => $total) {
			if (isset($total['order_total_id']) && $total['order_total_id']) {
				$orders->editTotal(['total' => $total, 'order_total_id' => $total['order_total_id']]);
			} else {
				unset($total['order_total_id']);
				$orders->addTotal(['total' => $total, 'order_id' => $order_id]);
			}
		}
	}
}
