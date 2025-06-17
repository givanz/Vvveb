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

use function \Vvveb\getCurrency;
use function \Vvveb\model;
use function \Vvveb\url;
use Vvveb\Sql\Product_Option_ValueSQL;
use Vvveb\Sql\Product_VariantSQL;
use Vvveb\Sql\ProductSQL;
use Vvveb\Sql\Subscription_PlanSQL;
use Vvveb\System\Images;
use Vvveb\System\Session;

class Cart {
	protected $cart = [];

	protected $session;

	protected $currency;

	protected $tax;

	protected $weight;

	protected $productModel = 'product';

	protected $cart_id;

	protected $encrypted_cart_id;

	protected $sessionKey = 'cart';

	protected $persist = 'session';

	protected $options;

	protected $products = [];

	protected $taxes = [];

	protected $totals = [];

	protected $total = 0;

	protected $total_tax = 0;

	protected $total_items = 0;

	protected $controlCache = true;

	use TaxTrait, ProductOptionTrait, CouponTrait, TotalTrait, DbTrait;

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
		$this->weight   = Weight :: getInstance();

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

		if ($this->total_items) {
			$this->disableCache();
		} else {
			$this->enableCache();
		}
	}

	public function create() {
		//calll only if you need a cart id beforehand, otherwise a cart will be created automatically when adding a product if it does not exist
		$this->write();

		return $this->cart_id;
	}

	public function getEncryptedId() {
		if ($this->encrypted_cart_id) {
			return $this->encrypted_cart_id;
		}

		$key                     = \Vvveb\getConfig('app.key');
		$this->encrypted_cart_id = \Vvveb\encrypt($key, $this->cart_id);

		return $this->encrypted_cart_id;
	}

	public function getCartUrl() {
		$id  = urlencode($this->getEncryptedId());
		$url = url('cart/cart/index', ['cart_id' => $id]);
		//app does not have url
		if (! $url) {
			$url = "/checkout/$id";
		}

		return $url;
	}

	public function getCheckoutUrl() {
		$id  = urlencode($this->getEncryptedId());
		$url = url('checkout/checkout/index', ['cart_id' => $id]);
		//app does not have url
		if (! $url) {
			$url = "/checkout/$id";
		}

		return $url;
	}

	public function getId() {
		return $this->cart_id;
	}

	public function updateCart() {
		$this->total       = 0;
		$this->total_items = 0;

		$results  = ['products' => [], 'count' => 0];
		$currency = getCurrency();

		if (! empty($this->products)) {
			$productIds           = [];
			$productOptions       = [];
			$productVariants      = [];
			$productSubscriptions = [];

			foreach ($this->products as $product) {
				$productId              = $product['product_id'];
				$productIds[$productId] = $productId;

				//get all product options to make one query to get all option values
				if (isset($product['option']) && is_array($product['option'])) {
					foreach ($product['option'] as $value) {
						if (is_numeric($value)) {
							$productOptions[$value] = $value;
						} else {
							$product_option_value_id                  = $value['product_option_value_id'];
							$productOptions[$product_option_value_id] = $product_option_value_id;
						}
					}
				}

				if (isset($product['subscription_plan_id']) && $product['subscription_plan_id']) {
					$productSubscriptions[$product['subscription_plan_id']] = $product['subscription_plan_id'];
				}

				if (isset($product['product_variant_id']) && $product['product_variant_id']) {
					$productVariants[$product['product_variant_id']] = $product['product_variant_id'];
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
				$productOptionValueSql = new Product_Option_ValueSQL();
				$optionResults         = $productOptionValueSql->getAll(
					['product_option_value_id' => array_values($productOptions)] + $this->options
				)['product_option_value'] ?? [];
			}

			// if products have subscriptions get all subscriptions in one query
			$subscriptionResults = [];

			if ($productSubscriptions) {
				$subscriptionPlanSql = new Subscription_PlanSQL();
				$subscriptionResults = $subscriptionPlanSql->getAll(
					['subscription_plan_id' => array_values($productSubscriptions)] + $this->options
				)['subscription_plan'] ?? [];
			}

			// if products have variants get all variants in one query
			$variantsResults = [];

			if ($productVariants) {
				$productVariantSql = new Product_VariantSQL();
				$variantsResults   = $productVariantSql->getAll(
					['product_variant_id' => array_values($productVariants)] + $this->options
				)['product_variant'] ?? [];
			}
		}

		$products       = $results['product'] ?? [];

		if ($products) {
			foreach ($this->products as $key => &$prod) {
				if (! isset($products[$product['product_id']])) {
					unset($this->products[$key]);

					continue;
				}

				$productId = $prod['product_id'];
				$product   = $products[$productId];

				$prod['price'] = (float)$product['price'];

				//add option value data and adjust price if necessary
				if (isset($prod['option']) && is_array($prod['option'])) {
					foreach ($prod['option'] as $option_id => $option) {
						if (is_numeric($option)) {
							$product_option_value_id = $option;
						} else {
							$product_option_value_id = $option['product_option_value_id'];
						}

						$value = $optionResults[$product_option_value_id];

						if ($value['price']) {
							if ($value['price_operator'] == '-') {
								$value['price'] -= (float)$value['price'];
							}

							$prod['price'] += (float) $value['price'];

							$value['price_tax']       = $this->tax->addTaxes($value['price'], $product['tax_type_id'] ?? 0);
							$value['price_formatted'] = $value['price_operator'] . $this->currency->format($value['price_tax']);
						}

						if ($value['weight']) {
							if ($value['weight_operator'] == '-') {
								$value['weight'] -= $value['weight'];
							}

							$prod['weight']            = ($prod['weight'] ?? 0) + $value['weight'];
							$value['weight_formatted'] = $value['weight'];
						}

						if ($value['points']) {
							if ($value['points_operator'] == '-') {
								$value['points'] -= $value['points'];
							}

							$prod['points'] += $value['points'];
							$value['points_formatted'] = $value['points'];
						}

						$prod['option_value'][$product_option_value_id] = $value;
					}
				}

				//add subscription data
				if (isset($prod['subscription_plan_id']) && $prod['subscription_plan_id']) {
					$prod['subscription']      = $subscriptionResults[$prod['subscription_plan_id']] ?? [];
					$prod['subscription_name'] = $prod['subscription']['name'] ?? '';
				}

				//if product variant override price and stock
				if (isset($prod['product_variant_id']) && $prod['product_variant_id']) {
					$variant         = $variantsResults[$prod['options']] ?? [];

					if ($variant && $variant['product_variant_id'] == $prod['product_variant_id']) {
						$prod['variant']        = $variant;
						$prod['price']          = $variant['price'];
						$prod['stock_quantity'] = $variant['stock_quantity'];
					}
				}

				$url                     = ['slug' => $product['slug'], 'product_id' => $product['product_id']];
				$prod['key']             = $key;
				$prod['url']             = htmlspecialchars(url('product/product/index', $url));
				$prod['remove-url']      = htmlspecialchars(url('cart/cart/remove', $url + ['key' => $key]));

				$prod['total']           = $prod['price'] * $prod['quantity'];
				$prod['total_formatted'] = $this->currency->format($prod['total']);

				$taxValue               = $this->tax->addTaxes($prod['price'], $product['tax_type_id'], true);
				$prod['price_tax']      = ($taxValue + $prod['price']);
				$prod['tax']            = $prod['price_tax'] * $prod['quantity'];
				$prod['total_tax']      = $prod['tax'];
				$prod['price_currency'] = $currency;

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

				$prod = array_replace($product, $prod);

				if (isset($product['image'])) {
					$prod['image'] = Images::image($product['image'], 'product', 'thumb');
				}

				unset($prod['content']);

				// options add to price
			}
		}

		$this->addTotal('sub_total', 'Sub-total', $this->total);

		//set cart cookie to disable cache if products in cart
		if ($this->total_items) {
			$this->disableCache();
		} else {
			$this->enableCache();
		}
		//write is done by addTotal
		//$this->write();

		return $results;
	}

	function add($productId, $quantity = 1, $option = [], $productVariantId = false, $subscriptionPlanId = false) {
		if (! $productId) {
			return false;
		}

		$key       = $productId;
		$options   = '';

		if ($option && is_array($option)) {
			ksort($option);
			$options = trim(str_replace('"', '', json_encode($option)), '{}');
			$key .= '_' . $options;
		} else {
			$option = [];
		}

		if ($subscriptionPlanId) {
			$key .= "_$subscriptionPlanId";
		}

		$key = str_replace('"', '', $key);

		if (isset($this->products[$key])) {
			$this->products[$key]['quantity'] += $quantity;
		} else {
			$this->products[$key] = [
				'product_id'           => (int) $productId,
				'quantity'             => (int)$quantity,
				'option'               => $option ?? [],
				'options'              => $options,
				'subscription_plan_id' => $subscriptionPlanId,
				'product_variant_id'   => $productVariantId,
			];
		}

		$this->updateCart();

		return $key;
	}

	function update($key, $quantity = 1, $option = [], $productVariantId = false, $subscriptionPlanId = false) {
		if (isset($this->products[$key])) {
			$this->products[$key]['quantity'] = max(1, $quantity);

			if ($option) {
				$this->products[$key]['option'] = $option;
			}

			if ($subscriptionPlanId) {
				$this->products[$key]['subscription_plan_id'] = $subscriptionPlanId;
			}

			if ($productVariantId) {
				$this->products[$key]['product_variant_id'] = $productVariantId;
			}

			$this->updateCart();

			return $key;
		}

		return false;
	}

	function getAll() {
		return $this->products ?? [];
	}

	function getNoProducts() {
		//return count($this->products ?? []);
		$total = 0;

		foreach ($this->products as $product) {
			$total += $product['quantity'];
		}

		return $total;
	}

	function remove($key) {
		if ($key) {
			if (is_array($key)) {
				foreach ($key as $value) {
					if (isset($this->products[$value])) {
						unset($this->products[$value]);
					}
				}
			} else {
				if (isset($this->products[$key])) {
					unset($this->products[$key]);
				}
			}

			$this->updateCart();

			return $key;
		}

		return false;
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
			if (isset($product['requires_shipping']) && $product['requires_shipping']) {
				$weight += $this->weight->convert($product['weight'], $product['weight_type_id'], $this->options['weight_type_id']) * $product['quantity'];
			}
		}

		return $weight;
	}

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
			if ($product['requires_shipping']) {
				return true;
			}
		}

		return false;
	}

	public function hasDownload() {
		foreach ($this->products as $product) {
			if ($product['digital_asset']) {
				return true;
			}
		}

		return false;
	}

	/*
		protected function read() {
			$data = $this->session->get($this->sessionKey);

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

			$this->session->set($this->sessionKey, $data);
		}

		public function empty() {
			$this->session->set($this->sessionKey, []);
			$this->enableCache();
		}
	*/
	public function enableCache() {
		//enable cache back by clearing the cart cookie
		if ($this->controlCache && isset($_COOKIE['cart'])) {
			setcookie('cart', '', time() - 3600, '/');
		}
	}

	public function disableCache() {
		//disable cache back setting the cart cookie
		if ($this->controlCache && ! isset($_COOKIE['cart'])) {
			setcookie('cart', '1', 0, '/');
		}
	}
}
