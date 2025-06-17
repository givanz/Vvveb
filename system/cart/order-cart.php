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
	protected $order_id;

	protected $controlCache = false;

	function __construct($order_id, $options = []) {
		$this->session  = Session :: getInstance();
		$this->currency = Currency :: getInstance($options);
		$this->tax      = Tax :: getInstance();
		$this->weight   = Weight :: getInstance();

		$this->tax->setRegionRules($options['country_id'], $options['region_id']);

		$this->order_id = $order_id;
		$this->cart_id  = $options['cart_id'] ?? null;
		$this->options  = $options;

		if ($this->cart_id && ! is_numeric($this->cart_id)) {
			$cart_id = urldecode($this->cart_id);
			$key     = \Vvveb\getConfig('app.key');
			$cart_id = \Vvveb\decrypt($key, $cart_id);

			if ($cart_id) {
				$this->encrypted_cart_id = $this->cart_id;
				$this->cart_id           = $cart_id;
			}
		}

		$this->read();

		if (! isset($this->total_items)) {
			$this->total_items = 0;
		}

		if (! isset($this->total)) {
			$this->total = 0;
		}

		if (! isset($this->products)) {
			$this->products = [];
		}
	}

	function addOrderProduct($productId, $orderProductId = false, $quantity = 1, $option = [], $subscriptionPlanId = false) {
		if (! $productId) {
			return false;
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

		//return $this->updateCart();
	}

	function addTotal($key, $title, $value, $text = '', $order_total_id = false) {
		$data = ['key' => $key, 'title' => $title, 'value' => $value, 'value_formatted' => $this->currency->format($value), 'text' => $text, 'order_total_id' => $order_total_id];

		if (isset($this->totals[$key])) {
			$this->totals[$key]['value']           = $value;
			$this->totals[$key]['value_formatted'] = $this->currency->format($value);
		} else {
			$this->totals[$key] = $data;
		}
		//$this->write();
	}

	protected function read() {
		$options = ['order_id' => (int)$this->order_id] + $this->options;

		$orders   = new OrderSQL();
		$results  = $orders->get($options);

		//$this->totals = $results['total'];
		if (isset($results['total']) && $results['total']) {
			foreach ($results['total'] as $total) {
				$this->addTotal($total['key'], $total['title'], $total['value'], $total['title'], $total['order_total_id']);
			}
		}
		//$this->products = $results['product'];
		if (isset($results['products']) && $results['products']) {
			foreach ($results['products'] as $product) {
				$option_value = $product['option_value'] ? json_decode($product['option_value'], true) : [];
				$this->addOrderProduct($product['product_id'], $product['order_product_id'], $product['quantity'], $option_value);
			}
		}

		$this->updateCart();
		/*

		if (is_array($data)) {
			foreach ($data as $property => $value) {
				$this->$property = $value;
			}
		}*/
	}

	public function write() {
		$this->addTaxTotal();
		$order_id = $this->order_id;
		$orders   = new OrderSQL();

		$data     = ['total' => $this->getGrandTotal()];
		$result   = $orders->edit(['order' => $data, 'order_id' => $order_id]);

		foreach ($this->products as $key => $product) {
			$product_options               = $product['option'];

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
		/*
		$checkoutInfo['products']        = $cart->getAll();
		$checkoutInfo['product_options'] = $cart->getProductOptions();
		$checkoutInfo['totals']          = $cart->getTotals();
		$checkoutInfo['total']           = $cart->getGrandTotal();
		
		foreach (['products', 'taxes', 'totals',  'total_items', 'coupons', 'product_options'] as $property) {
			$data[$property] = $this->$property;
		}

		$this->session->set($this->sessionKey, $data);
		*/
	}
}
