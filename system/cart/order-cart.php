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

use function \Vvveb\model;
use function \Vvveb\url;
use Vvveb\Sql\OrderSQL;
use Vvveb\Sql\Product_option_valueSQL;
use Vvveb\Sql\ProductSQL;
use Vvveb\System\Images;
use Vvveb\System\Session;

class OrderCart extends Cart {
	protected $cart = [];

	protected $session;

	protected $currency;

	protected $tax;

	protected $order_id;

	protected $productModel = 'product';

	protected $options;

	protected $order = [];

	protected $products = [];

	protected $taxes = [];

	protected $totals = [];

	protected $total = 0;

	protected $total_tax = 0;

	protected $total_items = 0;

	use TaxTrait, ProductOptionTrait, CouponTrait;

	function __construct($order_id, $options = []) {
		$this->session  = Session :: getInstance();
		$this->currency = Currency :: getInstance($options);
		$this->tax      = Tax :: getInstance();

		$this->tax->setRegionRules($options['country_id'], $options['region_id']);

		$this->order_id = $order_id;
		$this->options  = $options;
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

	public function updateCart() {
		$this->total       = 0;
		$this->total_items = 0;

		$results = ['products' => [], 'count' => 0];

		if (! empty($this->products)) {
			$productIds           = [];
			$productOptions       = [];
			$productSubscriptions = [];

			foreach ($this->products as $product) {
				$productId              = $product['product_id'];
				$productIds[$productId] = $productId;

				//get all product options to make one query to get all option values
				if (isset($product['option'])) {
					foreach ($product['option'] as $value) {
						if (is_numeric($value)) {
							$productOptions[$value] = $value;
						} else {
							$product_option_value_id                  = $value['product_option_value_id'];
							$productOptions[$product_option_value_id] = $product_option_value_id;
						}
					}
				}

				if (isset($product['subscription_plan_id'])) {
					$productSubscriptions[$productId] = $product['subscription_plan_id'];
				}
			}

			//get product data from db for products in cart
			$options =  [
				'product_id'            => $productIds,
			] + $this->options;

			$productSql = model($this->productModel); //new ProductSQL();
			$results    = $productSql->getAll(
				$options
			);

			// if products have options get all product options in one query
			$optionResults = [];

			if ($productOptions) {
				$productOptionValueSql = new Product_option_valueSQL();
				$optionResults         = $productOptionValueSql->getAll(
					['product_option_value_id' => array_values($productOptions)] + $this->options
				)['product_option_value'] ?? [];
			}
		}

		$products       = $results['products'] ?? [];

		if ($products) {
			foreach ($this->products as $key => &$prod) {
				if (! isset($products[$product['product_id']])) {
					unset($this->products[$key]);

					continue;
				}

				$productId = $prod['product_id'];
				$product   = $products[$productId];

				$prod['price'] = $product['price'];

				//add option value data and adjust price if necessary
				if (isset($prod['option'])) {
					foreach ($prod['option'] as $option_id => $option) {
						if (is_numeric($option)) {
							$product_option_value_id = $option;
						} else {
							$product_option_value_id = $option['product_option_value_id'];
						}

						$value = $optionResults[$product_option_value_id];

						if ($value['price']) {
							if ($value['price_operator'] == '-') {
								$value['price'] = -$value['price'];
							}

							$prod['price'] += $value['price'];
							$value['price_formatted'] = $this->currency->format($value['price']);
						}

						$prod['option_value'][$product_option_value_id] = $value;
					}
				}

				$prod['url']             = htmlentities(url('product/product/index', $product));
				$prod['remove-url']      = htmlentities(url('cart/cart/remove', $product));

				$prod['total']           = (int)$prod['price'] * $prod['quantity'];
				$prod['total_formatted'] = $this->currency->format($prod['total']);

				$taxValue             = $this->tax->addTaxes($prod['price'], $product['tax_type_id'], true);
				$prod['price_tax']    = ($taxValue + $prod['price']);
				$prod['tax']          = $prod['price_tax'] * $prod['quantity'];
				$prod['total_tax']    = $prod['tax'];

				$prod['price_tax_formatted'] = $this->currency->format($prod['price_tax']);
				$prod['price_formatted']     = $this->currency->format($product['price']);
				$prod['total_formatted']     = $this->currency->format($prod['total']);
				$prod['total_tax_formatted'] = $this->currency->format($prod['total_tax']);

				if (isset($products[$productId]['subscription_plan_id'])) {
					//$prod['subscription_plan_id'] = $products[$productId]['subscription_plan_id'];
				}

				$this->total += $prod['total'];
				$this->total_tax += $prod['total_tax'];
				$this->total_items += $prod['quantity'];

				$prod = array_merge($prod, $product);

				if (isset($product['image'])) {
					$prod['image'] = Images::image($product['image'], 'product', 'thumb');
				}

				// options add to price
			}
		}

		$this->addTotal('sub_total', 'Sub-total', $this->total);
		//write is done by addTotal
		//$this->write();
		return $results;
	}

	function add($productId, $orderProductId = false, $quantity = 1, $option = [], $subscriptionPlanId = false) {
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
				$this->addTotal($total['key'], $total['title'], $total['value'], '', $total['order_total_id']);
			}
		}
		//$this->products = $results['products'];
		if (isset($results['products']) && $results['products']) {
			foreach ($results['products'] as $product) {
				$option_value = $product['option_value'] ? json_decode($product['option_value'], true) : [];
				$this->add($product['product_id'], $product['order_product_id'], $product['quantity'], $option_value);
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
