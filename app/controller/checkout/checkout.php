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
use Vvveb\Controller\Cart\CouponTrait;
use Vvveb\Controller\User\LoginTrait;
use function Vvveb\email;
use function Vvveb\prefixArrayKeys;
use function Vvveb\siteSettings;
use Vvveb\Sql\CountrySQL;
use Vvveb\Sql\RegionSQL;
use Vvveb\Sql\User_AddressSQL;
use Vvveb\System\CacheManager;
use Vvveb\System\Cart\Cart;
use Vvveb\System\Cart\Order;
use Vvveb\System\Event;
use Vvveb\System\Payment;
use Vvveb\System\Shipping;
use Vvveb\System\Sites;
use Vvveb\System\User\User;
use Vvveb\System\Validator;
use function Vvveb\url;

class Checkout extends Base {
	use LoginTrait, CouponTrait;

	private $cart;

	/*
	use LoginTrait {
		login as protected loginBase;
	}
	

	function login() {
		$this->loginBase();
		
		return $this->index();
	}
	*/

	function init() {
		parent::init();

		$options = array_intersect_key($this->global['site'],
		array_flip(['weight_type_id', 'length_type_id', 'currency_id', 'country_id']));

		$cart_id = false;

		if (isset($this->request->get['cart_id'])) {
			$cart_id = $options['cart_id'] = $this->request->get['cart_id'];
		}

		$this->cart = Cart::getInstance($this->global + $options);

		if ($cart_id) {
			$this->cart->loadCart($cart_id);
		}
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
			$regions               = $region->getAll($options)['region'] ?? [];
		}

		$this->response->setType('json');
		$this->response->output($regions);
	}

	private function data(&$checkoutInfo) {
		$countryModel      = new CountrySQL();
		$options           = $this->global;
		$options['status'] = 1;
		unset($options['limit']);
		$country               = $countryModel->getAll($options);
		$this->view->countries = $country['country'] ?? [];

		//set default contry and region
		$checkoutInfo['billing_country_id']  = $checkoutInfo['billing_country_id'] ?? $this->global['site']['country_id'] ?? false;
		$checkoutInfo['shipping_country_id'] = $checkoutInfo['shipping_country_id'] ?? $this->global['site']['country_id'] ?? false;
		$checkoutInfo['shipping_region_id']  = $checkoutInfo['shipping_region_id'] ?? $this->global['site']['region_id'] ?? false;
		$checkoutInfo['billing_region_id']   = $checkoutInfo['billing_region_id'] ?? $this->global['site']['region_id'] ?? false;
		$country_id                          = $checkoutInfo['billing_country_id'] ?? $checkoutInfo['shipping_country_id'] ?? false;

		if ($country_id) {
			$region   = new RegionSQL();
			$regions	 = $region->getAll(['country_id' => $country_id]);

			$this->view->regions = $regions['region'] ?? [];
		}
		$this->view->regionsUrl   = url(['module' => 'checkout/checkout', 'action' => 'regions']);
	}

	function index() {
		//buy now product
		if (isset($this->request->get['product_id'])) {
			$productId          = $this->request->get['product_id'];
			$quantity           = $this->request->post['quantity'] ?? 1;
			$option             = $this->request->post['option'] ?? [];
			$subscriptionPlanId = $this->request->post['subscription_plan_id'] ?? false;
			$productVariantId   = $this->request->post['product_variant_id'] ?? false;
			$this->cart->add($productId, $quantity, $option, $productVariantId, $subscriptionPlanId);
		}

		if (! $this->cart->hasProducts()) {
			return $this->redirect('cart/cart/index');
		}

		if (isset($this->request->post['login'])) {
			return $this->login();
		}

		$order    = Order::getInstance();

		$checkoutInfo            = $this->session->get('checkout') ?? [];
		$grandTotal              = $this->cart->getGrandTotal();
		$hasShipping             = $this->cart->hasShipping();
		$hasPayment              = ($grandTotal > 0);
		$this->view->hasShipping = $hasShipping;
		$this->view->hasPayment  = $hasPayment;

		$payment              = Payment::getInstance();
		$this->view->payment  = $payment->getMethods($checkoutInfo);

		if ($hasShipping) {
			$shipping             = Shipping::getInstance();
			$this->view->shipping = $shipping->getMethods($checkoutInfo);
		}

		if ($hasShipping && isset($this->request->post['shipping_method'])) {
			$shipping_method = $this->request->post['shipping_method'];
			$shipping->setMethod($shipping_method);
			$checkoutInfo['shipping_method'] = $shipping_method;
			$this->view->shipping_method     = $shipping_method;
		}

		if ($hasPayment && isset($this->request->post['payment_method'])) {
			$payment_method = $this->request->post['payment_method'];
			$payment->setMethod($payment_method);
			$checkoutInfo['payment_method'] = $payment_method;
			$this->view->payment_method     = $payment_method;
		}

		$this->data($checkoutInfo);

		if ($this->request->post) {
			if (isset($this->request->post['phone_number']) ||
				isset($this->request->post['billing_address']) ||
				isset($this->request->post['billing_address_id']) || /*
				isset($this->request->post['shipping_method']) ||
				isset($this->request->post['payment_method']) ||*/
				isset($this->request->post['email'])) {
				$rules = ['checkout'];

				// guest checkout
				if (! $this->global['user_id']) {
					//password is provided
					if (isset($this->request->post['register']) && $this->request->post['register'] == 'true') {
						$rules[] = 'checkout_signup';
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
						$this->request->post['billing_address']['first_name'] = $this->request->post['billing_address']['first_name'] ?? $this->request->post['first_name'] ?? '';
						$this->request->post['billing_address']['last_name']  = $this->request->post['billing_address']['last_name'] ?? $this->request->post['last_name'] ?? '';

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
						//$this->request->post += prefixArrayKeys('billing_', $address);
					}
				}

				//if card data then validate
				if (isset($this->request->post['card'])) {
					$rules[] = 'card';
				}

				if ($hasShipping) {
					//different shipping address is selected
					if (isset($this->request->post['different_shipping_address']) && ! empty($this->request->post['different_shipping_address'])) {
						$rules[] = 'checkout_shipping';

						if (isset($this->request->post['shipping_address'])) {
							$shipping_address = $this->request->post['shipping_address'];
							//$this->request->post += prefixArrayKeys('shipping_', $shipping_address);
						}
					} else {
						//use billing address as shipping address
						$rules[] = 'checkout_shipping';

						if (isset($this->request->post['billing_address'])) {
							$this->request->post['shipping_address'] = $this->request->post['billing_address'];
							//$this->request->post += prefixArrayKeys('shipping_', $this->request->post['shipping_address']);
						}
					}
				}

				//allow only fields that are in the validator list and remove the rest
				$post         = $this->request->post;
				$validator    = new Validator($rules);

				if (! $hasShipping) {
					$validator->removeRule('shipping_method');
				}

				if (! $hasPayment) {
					$validator->removeRule('payment_method');
				}

				$checkoutInfo = ($validator->filter($this->request->post) ?? []) + $checkoutInfo;
				$this->session->set('checkout', $checkoutInfo);

				if (($errors = $validator->validate($this->request->post)) === true) {
					$checkoutInfo['products']        = $this->cart->getAll();
					$checkoutInfo['product_options'] = $this->cart->getProductOptions();
					$checkoutInfo['totals']          = $this->cart->getTotals();
					$checkoutInfo['total']           = $grandTotal;
					$checkoutInfo += prefixArrayKeys('shipping_', $checkoutInfo['shipping_address']);
					$checkoutInfo += prefixArrayKeys('billing_', $checkoutInfo['billing_address']);
					$checkoutInfo += $this->global;

					//create user account if password is provided
					if (isset($checkoutInfo['password']) && ! $this->global['user_id']) {
						$userInfo = [];

						foreach (['first_name', 'last_name', 'email', 'phone_number', 'password'] as $field) {
							$userInfo[$field] = $checkoutInfo[$field] ?? NULL;
						}

						$userInfo['display_name'] = $userInfo['first_name'] . ' ' . $userInfo['last_name'];
						$userInfo['username']     = strtolower(str_replace(' ', '', $userInfo['first_name'] . $userInfo['last_name'])) . '-' . rand();

						$result = User::add($userInfo);

						if ($result) {
							if (isset($result['user'])) {
								$checkoutInfo['user_id'] = $result['user'] ?? NULL;
								$userInfo                = User::get(['user_id' => $checkoutInfo['user_id']]);
								//check if user was added before automatic login
								if ($userInfo) {
									\Vvveb\session(['user' => $userInfo]);
									$this->view->global['user_id'] = $userInfo['user_id'];
								}
							} else {
								$this->view->errors[] = __('This email is already in use. Please use another one or login.');

								return;
							}
						} else {
							$this->view->errors[] = __('Error creating account!');

							return;
						}
					}

					$user_id  = $checkoutInfo['user_id'] ?? $this->global['user_id'];

					//if new address add to user account
					if (empty($this->request->post['billing_address_id']) && $user_id) {
						$addressSql = new User_AddressSQL();
						$address    = $addressSql->add(['user_address' => $this->request->post['billing_address'] + ['user_id' => $user_id]]);
					}

					//if account does not have a phone number set add phone number to user profile for next order
					if ($this->global['user_id'] && ! $this->global['user']['phone_number']) {
						User::update(['phone_number' => $checkoutInfo['phone_number']], ['user_id' => $this->global['user_id']]);
						User::session(['phone_number' => $checkoutInfo['phone_number']]);
					}

					if (! $checkoutInfo['user_id']) {
						unset($checkoutInfo['user_id']); //if anonymous then unset user_id
					}

					$checkoutInfo['shipping_data'] = '';

					if ($hasShipping) {
						$checkoutInfo['shipping_data'] = json_encode($this->view->shipping[$checkoutInfo['shipping_method']] ?? []);
					}

					$checkoutInfo['payment_data'] = '';

					if ($hasPayment) {
						$checkoutInfo['payment_data']  = json_encode($this->view->payment[$checkoutInfo['payment_method']] ?? []);
					}
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

					list($checkoutInfo) = Event::trigger(__CLASS__, 'add', $checkoutInfo);

					$checkoutInfo = $order->add($checkoutInfo);

					if ($checkoutInfo && $checkoutInfo['order_id']) {
						$order_id                           = $checkoutInfo['order_id'];
						$customer_order_id                  = $checkoutInfo['customer_order_id'];
						$this->request->request['order_id'] = $order_id;
						$checkoutInfo['order_id']           = $order_id;

						$this->view->messages[] = __('Order placed!');
						$this->session->set('order', $checkoutInfo);
						$site = siteSettings();

						$shippingData = $checkoutInfo['shipping_data'];
						$paymentData  = $checkoutInfo['payment_data'];

						try {
							if ($hasShipping) {
								$shippingOk = $shipping->ship($checkoutInfo);
							}

							if ($hasPayment) {
								$paymentOk  = $payment->authorize($checkoutInfo);
							}
						} catch (\Exception $e) {
							$this->view->errors[] = $e->getMessage();
						}

						list($checkoutInfo, $order_id, $site) = Event::trigger(__CLASS__, 'add:after', $checkoutInfo, $order_id, $site);

						if (($shippingData != $checkoutInfo['shipping_data']) || ($paymentData != $checkoutInfo['payment_data'])) {
							$order->edit(['shipping_data' => $checkoutInfo['shipping_data'], 'payment_data' => $checkoutInfo['payment_data']], $order_id);
						}

						try {
							$error =  __('Error sending order confirmation mail!');
							$title = sprintf(__('Order confirmation #%s'), $customer_order_id);

							if (! email([$checkoutInfo['email'], $site['admin-email']], $title, 'order/new', $checkoutInfo + ['title' => $title])) {
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

						$this->cart->empty();

						// clear notifications cache
						CacheManager :: clearObjectCache('component', 'notifications');

						return $this->redirect('checkout/confirm/index');
					} else {
						$this->view->errors[] = __('Error creating checkout!');
					}
				} else {
					$this->view->errors = $errors;
				}
			}

			$this->session->set('checkout', $checkoutInfo);
		}

		$this->view->checkout = $checkoutInfo;
	}
}
