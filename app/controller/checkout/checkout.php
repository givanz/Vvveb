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

namespace Vvveb\Controller\Checkout;

use function Vvveb\__;
use Vvveb\Controller\Base;
use Vvveb\Controller\User\LoginTrait;
use function Vvveb\email;
use function Vvveb\prefixArrayKeys;
use function Vvveb\siteSettings;
use Vvveb\Sql\CountrySQL;
use Vvveb\Sql\RegionSQL;
use Vvveb\Sql\User_AddressSQL;
use Vvveb\System\Cart\Cart;
use Vvveb\System\Cart\Order;
use Vvveb\System\Core\View;
use Vvveb\System\Payment;
use Vvveb\System\Shipping;
use Vvveb\System\Sites;
use Vvveb\System\User\User;
use Vvveb\System\Validator;
use function Vvveb\url;

class Checkout extends Base {
	use LoginTrait;

	/*
	use LoginTrait {
		login as protected loginBase;
	}
	

	function login() {
		$this->loginBase();
		
		return $this->index();
	}
	*/
	function coupon() {
		$this->response->setType('json');
		$this->response->output($result);
	}

	function regions() {
		$country_id   = $this->request->get['country_id'] ?? false;
		$regions      = [];

		if ($country_id) {
			$region              = new RegionSQL();
			$options             = $this->global;
			$options['status']   = 1;
			unset($options['limit']);
			$options['country_id'] = $country_id;
			$regions			            = $region->getAll($options)['region'] ?? [];
		}

		$this->response->setType('json');
		$this->response->output($regions);
	}

	private function data() {
		$countryModel      = new CountrySQL();
		$options           = $this->global;
		$options['status'] = 1;
		unset($options['limit']);
		$country	              = $countryModel->getAll($options);
		$this->view->countries = $country['country'] ?? [];

		//set Regions for default store country
		/*
		$region  = new RegionSQL();
		$regions	 = $countryModel->getAll($options);

		$options['country_id'] = $country_id;
		$this->view->regions = $regions['region'] ?? [];
		*/
		$this->view->regionsUrl   = url(['module' => 'checkout/checkout', 'action' => 'regions']);
	}

	function index() {
		$cart = Cart :: getInstance($this->global);

		//buy now product
		if (isset($this->request->request['product_id'])) {
			$productId = $this->request->request['product_id'];
			$quantity  = $this->request->post['quantity'] ?? 1;
			$cart->add($productId, $quantity);
		}

		if (! $cart->hasProducts()) {
			return $this->redirect('cart/cart/index');
		}

		$payment  = Payment::getInstance();
		$shipping = Shipping::getInstance();
		$order    = Order::getInstance();

		$this->view->payment  = $payment->getMethods();
		$this->view->shipping = $shipping->getMethods();
		$this->data();

		if (isset($this->request->post['login'])) {
			return $this->login();
		}

		if (isset($this->request->post['shipping_method'])) {
			$shipping->setMethod($this->request->post['shipping_method']);
			$this->view->shipping_method = $this->request->post['shipping_method'];
		}

		if (isset($this->request->post['payment_method'])) {
			$payment->setMethod($this->request->post['payment_method']);
			$this->view->payment_method = $this->request->post['payment_method'];
		}

		if ($this->request->post && (
				isset($this->request->post['phone_number']) ||
				isset($this->request->post['billing_address']) ||
				isset($this->request->post['billing_address_id']) ||
				isset($this->request->post['email']))) {
			$rules = ['checkout'];

			// guest checkout
			if (! $this->global['user_id']) {
				//password is provided
				if (isset($this->request->post['register']) && $this->request->post['register'] == 'true') {
					$rules[] = 'signup';
				} else {
					$rules[] = 'guest';
				}
			} else {
				//registered user
				$rules[] = 'guest';

				foreach (['first_name', 'last_name', 'email', 'phone_number'] as $field) {
					$this->request->post[$field] = $this->request->post[$field] ?? $this->global['user'][$field];
				}
			}

			//billing address address check
			if (empty($this->request->post['billing_address_id'])) {
				$rules[] = 'checkout_billing';

				if (isset($this->request->post['billing_address'])) {
					//if billing name is missing use account name
					//$this->request->post['billing_address']['first_name'] ??= $this->request->post['first_name'];
					//$this->request->post['billing_address']['last_name'] ??= $this->request->post['last_name'];
					$this->request->post['billing_address']['first_name'] = $this->request->post['billing_address']['first_name'] ?? $this->request->post['first_name'];
					$this->request->post['billing_address']['last_name']  = $this->request->post['billing_address']['last_name'] ?? $this->request->post['last_name'];

					$billing_address = $this->request->post['billing_address'];
					$this->request->post += prefixArrayKeys('billing_', $billing_address);
				}
			} else {
				//user address
				$rules[]            = 'checkout_billing';
				$billing_address_id = $this->request->post['billing_address_id'];
				$addressSql         = new User_AddressSQL();
				$address            = $addressSql->get($this->global + ['user_address_id' => $billing_address_id]);

				if ($address) {
					$this->request->post['billing_address'] = $address;
					$this->request->post += prefixArrayKeys('billing_', $address);
				}
			}

			//different shipping address is selected
			if (isset($this->request->post['different_shipping_address']) && ! empty($this->request->post['different_shipping_address'])) {
				$rules[] = 'checkout_shipping';

				if (isset($this->request->post['shipping_address'])) {
					$shipping_address = $this->request->post['shipping_address'];
					$this->request->post += prefixArrayKeys('shipping_', $shipping_address);
				}
			} else {
				//use billing address as shipping address
				$rules[] = 'checkout_shipping';

				if (isset($this->request->post['billing_address'])) {
					$this->request->post['shipping_address'] = $this->request->post['billing_address'];
					$this->request->post += prefixArrayKeys('shipping_', $this->request->post['shipping_address']);
				}
			}

			$validator                = new Validator($rules);
			$checkoutInfo             = $validator->filter($this->request->post);

			if (($errors = $validator->validate($this->request->post)) === true) {
				//allow only fields that are in the validator list and remove the rest

				$checkoutInfo             = $validator->filter($this->request->post);
				$checkoutInfo['products'] = $cart->getAll();
				$checkoutInfo['totals']   = $cart->getTotals();
				$checkoutInfo['total']    = $cart->getGrandTotal();
				$checkoutInfo += $this->global;

				//create user account if password is provided
				if (isset($checkoutInfo['password'])) {
					$userInfo = [];

					foreach (['first_name', 'last_name', 'email', 'phone_number', 'password'] as $field) {
						$userInfo[$field] = $checkoutInfo[$field] ?? NULL;
					}
					$userInfo['display_name'] = $userInfo['first_name'] . ' ' . $userInfo['last_name'];
					$userInfo['username']     = str_replace(' ', '', $userInfo['first_name'] . $userInfo['last_name']);

					$error =  __('Error creating account!');

					if ($result = User::add($userInfo)) {
						$checkoutInfo['user_id'] = $result['user'] ?? NULL;
					} else {
						$this->view->errors[] = $error;
					}
				}

				if (! $checkoutInfo['user_id']) {
					unset($checkoutInfo['user_id']); //if anonymous then unset user_id
				}

				$checkoutInfo['shipping_data'] = json_encode($this->view->shipping[$checkoutInfo['shipping_method']] ?? []);
				$checkoutInfo['payment_data']  = json_encode($this->view->payment[$checkoutInfo['payment_method']] ?? []);
				//default order status
				$checkoutInfo['order_status_id']  = 1;

				$site = Sites :: getSiteData();

				$order_url = url('user/orders', [
					'host'   => $site['host'] ?? false,
					'scheme' => $_SERVER['REQUEST_SCHEME'] ?? 'http',
				]);

				$checkoutInfo['site_url']  = $site['host'];
				$checkoutInfo['site_name'] = $site['name'];

				$this->view->errors = [];

				$order = $order->add($checkoutInfo);

				if ($order && is_array($order)) {
					$order_id                           = $order['order'];
					$this->request->request['order_id'] = $order_id;

					$this->view->messages[] = __('Order placed!');
					$this->session->set('order', $order);
					$cart->empty();
					$site = siteSettings();

					try {
						$error =  __('Error sending order confirmation mail!');

						if (! email([$checkoutInfo['email'], $site['admin-email']], sprintf(__('Order confirmation #%s'), $order_id), 'order/new', $checkoutInfo)) {
							$this->session->set('errors', $error);
							$this->view->errors[] = $error;
						}
					} catch (\Exception $e) {
						if (DEBUG) {
							$error .= "\n" . $e->getMessage();
						}
						$this->session->set('errors', $error);
						$this->view->errors[] = $error;
					}

					return $this->redirect('checkout/confirm/index');
				} else {
					$this->view->errors[] = __('Error creating checkout!');
				}
			} else {
				$this->view->errors = $errors;
			}
		}
	}
}
