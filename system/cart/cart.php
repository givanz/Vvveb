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

use function \Vvveb\url;
use Vvveb\Sql\Product_Option_ValueSQL;
use Vvveb\Sql\ProductSQL;
use Vvveb\System\Images;
use Vvveb\System\Session;

class Cart {
	protected $cart = [];

	private $session;

	private $currency;

	private $tax;

	protected $options;

	protected $products = [];

	protected $taxes = [];

	protected $totals = [];

	protected $total = 0;

	protected $total_tax = 0;

	protected $total_items = 0;

	use TaxTrait, ProductOptionTrait, CouponTrait;

	public static function getInstance($options = []) {
		static $inst = null;

		if ($inst === null) {
			$inst = new Cart($options);
		}

		return $inst;
	}

	private function __construct($options = []) {
		$this->session  = Session :: getInstance();
		$this->currency = Currency :: getInstance($options);
		$this->tax      = Tax :: getInstance();

		$this->options = $options;
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

			$productSql = new ProductSQL();
			$results    = $productSql->getAll(
				$options
			);

			// if products have options get all product options in one query
			$optionResults = [];

			if ($productOptions) {
				$productOptionValueSql = new Product_Option_ValueSQL();
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
				if ($prod['option']) {
					foreach ($prod['option'] as $option_id => $product_option_value_id) {
						$value                                          = $optionResults[$product_option_value_id];
						$prod['option_value'][$product_option_value_id] = $value;

						if ($value['price']) {
							if ($value['price_operator'] == '-') {
								$prod['price'] -= $value['price'];
							} else {
								$prod['price'] += $value['price'];
							}
						}
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

	function add($productId, $quantity = 1, $option = [], $subscriptionPlanId = false) {
		if (! $productId) {
			return false;
		}

		$key = $productId;

		if ($option) {
			$key .= '_' . json_encode($option);
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
				'quantity'             => $quantity,
				'option'               => $option,
				'subscription_plan_id' => $subscriptionPlanId,
			];
		}

		return $this->updateCart();
	}

	function update($key, $quantity = 1, $option = [], $subscriptionPlanId = false) {
		if (isset($this->products[$key])) {
			$this->products[$key]['quantity'] = max(1, $quantity);

			if ($option) {
				$this->products[$key]['option'] = $option;
			}

			if ($subscriptionPlanId) {
				$this->products[$key]['subscription_plan_id'] = $subscriptionPlanId;
			}

			return $this->updateCart();
		}
	}

	function getAll() {
		return $this->products ?? [];
	}

	function getNoProducts() {
		return count($this->products ?? []);
	}

	function remove($key) {
		unset($this->products[$key]);

		return $this->updateCart();
	}

	public function getSubscription() {
		$product_data = [];

		foreach ($this->products as $value) {
			if ($value['subscription']) {
				$product_data[] = $value;
			}
		}

		return $product_data;
	}

	public function getWeight() {
		$weight = 0;

		foreach ($this->products as $product) {
			if ($product['shipping']) {
				$weight += $this->weight->convert($product['weight'], $product['weight_type_id'], $this->config->get('config_weight_type_id'));
			}
		}

		return $weight;
	}

	public function getSubTotal() {
		$total = 0;

		foreach ($this->products as $product) {
			$total += $product['total'];
		}

		return $total;
	}

	public function getGrandTotal() {
		$sum = 0;

		if ($this->totals) {
			foreach ($this->totals as $total) {
				$sum += $total['value'] ?? 0;
			}
		}

		return $sum;
	}

	public function getGrandTotalFormatted() {
		$sum = $this->getGrandTotal();

		return $this->currency->format($sum);
	}

	function addTotal($key, $title, $value, $text = '') {
		$data = ['key' => $key, 'title' => $title, 'value' => $value, 'value_formatted' => $this->currency->format($value), 'text' => $text];

		$this->totals[$key] = $data;
		$this->write();
	}

	function removeTotal($key) {
		unset($this->totals[$key]);
	}

	function getTotals() {
		//include taxes
		$this->addTaxTotal();

		return $this->totals;
	}

	/*
		public function getTotal() {
			$total = 0;

			foreach ($this->products as $product) {
				$total += $this->tax->calculate($product['price'], $product['tax_type_id'], $this->config->get('config_tax')) * $product['quantity'];
			}

			return $total;
		}
	*/
	public function countProducts() {
		$product_total = 0;

		$products = $this->products;

		foreach ($products as $product) {
			$product_total += $product['quantity'];
		}

		return $product_total;
	}

	public function hasProducts() {
		return count($this->products);
	}

	public function hasSubscription() {
		return count($this->getSubscription());
	}

	public function hasStock() {
		foreach ($this->products as $product) {
			if (! $product['stock']) {
				return false;
			}
		}

		return true;
	}

	public function hasShipping() {
		foreach ($this->products as $product) {
			if ($product['shipping']) {
				return true;
			}
		}

		return false;
	}

	public function hasDownload() {
		foreach ($this->products as $product) {
			if ($product['download']) {
				return true;
			}
		}

		return false;
	}

	protected function read() {
		$data = $this->session->get('cart');

		if (is_array($data)) {
			foreach ($data as $property => $value) {
				$this->$property = $value;
			}
		}
	}

	protected function write() {
		//$data = get_object_vars($this);
		foreach (['products', 'taxes', 'totals',  'total_items', 'coupons', 'product_options'] as $property) {
			$data[$property] = $this->$property;
		}

		$this->session->set('cart', $data);
	}

	public function empty() {
		$this->session->set('cart', []);
	}
}
