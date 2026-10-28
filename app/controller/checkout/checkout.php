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
use Vvveb\Controller\Cart\CartTrait;
use Vvveb\Controller\Cart\CouponTrait;
use Vvveb\Controller\User\LoginTrait;
use function Vvveb\email;
use function Vvveb\model;
use function Vvveb\prefixArrayKeys;
use function Vvveb\siteSettings;
use Vvveb\Sql\CountrySQL;
use Vvveb\Sql\RegionSQL;
use Vvveb\Sql\User_addressSQL;
use Vvveb\System\CacheManager;
use Vvveb\System\Cart\Cart;
use Vvveb\System\Cart\Order;
use Vvveb\System\Event;
use Vvveb\System\Sites;
use Vvveb\System\User\User;
use Vvveb\System\Validator;
use function Vvveb\url;

class Checkout extends Base {
	use CartTrait;
	use CouponTrait;
	use LoginTrait;

	/** @var Cart|null Shopping cart instance. */
	private ?Cart $cart = null;

	/**
	 * Initialise the cart for the checkout process.
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

		$this->cart = Cart::getInstance($this->global + $options);

		if ($encrypted_cart_id) {
			if ($cart_id = $this->cart->setEncryptedId($encrypted_cart_id)) {
				$this->cart->loadCart($cart_id);
			}
		}

		return null;
	}

	/**
	 * Return regions for a given country as JSON (AJAX endpoint).
	 *
	 * @return void
	 */
	public function regions(): void {
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

	/**
	 * Load shared checkout data: countries and regions for address forms.
	 *
	 * @param array<string, mixed> &$checkoutInfo
	 *
	 * @return void
	 */
	private function data(array &$checkoutInfo): void {
		$countryModel      = new CountrySQL();
		$options           = $this->global;
		$options['status'] = 1;
		unset($options['limit']);
		$country               = $countryModel->getAll($options);
		$this->view->countries = $country['country'] ?? [];

		//set default country and region
		$checkoutInfo['billing_country_id'] ??= $this->global['site']['country_id'] ?? false;
		$checkoutInfo['shipping_country_id'] ??= $this->global['site']['country_id'] ?? false;
		$checkoutInfo['shipping_region_id'] ??= $this->global['site']['region_id'] ?? false;
		$checkoutInfo['billing_region_id'] ??= $this->global['site']['region_id'] ?? false;
		$country_id                          = $checkoutInfo['billing_country_id'] ?: $checkoutInfo['shipping_country_id'] ?: false;

		if ($country_id) {
			$region   = new RegionSQL();
			$regions  = $region->getAll(['country_id' => $country_id]);

			$this->view->regions = $regions['region'] ?? [];
		}
		$this->view->regionsUrl   = url(['module' => 'checkout/checkout', 'action' => 'regions']);
	}

	/**
	 * Create pending core subscription rows for subscription products.
	 *
	 * Gateway-agnostic: every payment method gets a local pending record at
	 * checkout time. Gateway-backed methods (Stripe) claim these rows when
	 * provisioning instead of inserting duplicates; manual methods
	 * (bank transfer, COD) stay pending until confirmed.
	 * Idempotent per order: retried submits skip already created rows.
	 *
	 * @param int|string           $order_id     Order id.
	 * @param array<string, mixed> $checkoutInfo Checkout information.
	 *
	 * @return void
	 */
	private function createPendingSubscriptions(int|string $order_id, array $checkoutInfo): void {
		$items = $this->cart->getSubscription();

		if (!$items) {
			return;
		}

		try {
			$model    = model('subscription');
			$userId   = (int)($checkoutInfo['user_id'] ?? $this->global['user_id'] ?? 0);
			$existing = $model->getAll(['user_id' => $userId, 'language_id' => $this->global['language_id'] ?? 1] + $this->global)['subscription'] ?? [];
			$have     = [];

			foreach ($existing as $row) {
				if ((int)($row['order_id'] ?? 0) === (int)$order_id) {
					$have[(int)($row['subscription_plan_id'] ?? 0) . ':' . (int)($row['product_id'] ?? 0)] = true;
				}
			}

			foreach ($items as $item) {
				$planId    = (int)($item['subscription_plan_id'] ?? 0);
				$productId = (int)($item['product_id'] ?? 0);
				$plan      = $item['subscription'] ?? [];

				if (!$planId || !$productId || empty($plan) || isset($have["$planId:$productId"])) {
					continue;
				}

				$period = $plan['period'] ?? 'month';
				$cycle  = max(1, (int)($plan['cycle'] ?? 1));

				try {
					$next = (new \DateTime())->modify("+$cycle $period")->format('Y-m-d H:i:s');
				} catch (\Throwable $e) {
					$next = date('Y-m-d H:i:s', strtotime('+1 month'));
				}

				$trialStatus = !empty($plan['trial_status']) && !empty($plan['trial_length']);

				$model->add(['subscription' => [
					'order_id'               => (int)$order_id,
					'order_product_id'       => 0,
					'site_id'                => SITE_ID,
					'user_id'                => $userId,
					'payment_method'         => $checkoutInfo['payment_method'] ?? '',
					'product_id'             => $productId,
					'quantity'               => (int)($item['quantity'] ?? 1),
					'subscription_plan_id'   => $planId,
					'price'                  => (float)($item['price'] ?? 0),
					'period'                 => $period,
					'cycle'                  => $cycle,
					'length'                 => (int)($plan['length'] ?? 0),
					'subscription_status_id' => 1,
					'date_next'              => $next,
					'trial_status'           => $trialStatus ? 1 : 0,
					'trial_price'            => (float)($item['trial_price'] ?? 0),
					'trial_period'           => $plan['trial_period'] ?? 'day',
					'trial_cycle'            => max(1, (int)($plan['trial_cycle'] ?? 1)),
					'trial_length'           => (int)($plan['trial_length'] ?? 0),
					'trial_left'             => (int)($plan['trial_length'] ?? 0),
					'notes'                  => '',
					'created_at'             => date('Y-m-d H:i:s'),
					'updated_at'             => date('Y-m-d H:i:s'),
				]] + $this->global);

				$have["$planId:$productId"] = true;
			}
		} catch (\Throwable $e) {
			//subscription rows are auxiliary, never block the order
		}
	}

	/**
	 * Rotate the single-use checkout submit token.
	 *
	 * @return string New token stored in the session.
	 */
	private function rotateCheckoutToken(): string {
		$tokens   = $this->session->get('checkout_tokens') ?? [];

		if (!is_array($tokens)) {
			$tokens = [];
		}

		$token    = bin2hex(random_bytes(16));
		$tokens[] = $token;
		$this->session->set('checkout_tokens', array_slice($tokens, -5));

		return $token;
	}

	/**
	 * Consume a submit token: true once, then spent (double posts fail).
	 *
	 * @param string $token Posted token.
	 *
	 * @return bool True when the token was valid and is now spent.
	 */
	private function consumeCheckoutToken(string $token): bool {
		$tokens = $this->session->get('checkout_tokens') ?? [];

		if (!is_array($tokens) || $token === '') {
			return false;
		}

		$key = array_search($token, $tokens, true);

		if ($key === false) {
			return false;
		}

		unset($tokens[$key]);
		$this->session->set('checkout_tokens', array_values($tokens));

		return true;
	}

	/**
	 * Current (newest) submit token, creating one when the pool is empty.
	 *
	 * @return string Token for the checkout form.
	 */
	private function currentCheckoutToken(): string {
		$tokens = $this->session->get('checkout_tokens') ?? [];

		if (!is_array($tokens) || !$tokens) {
			return $this->rotateCheckoutToken();
		}

		return (string)end($tokens);
	}

	/**
	 * Main checkout action — handles the entire checkout flow.
	 *
	 * @return void
	 */
	public function index(): void {
		//buy now product
		if (isset($this->request->request['product_id'])) {
			$productId          = $this->request->request['product_id'];
			$quantity           = $this->request->post['quantity'] ?? 1;
			$option             = $this->request->post['option'] ?? [];
			$subscriptionPlanId = $this->request->post['subscription_plan_id'] ?? false;
			$productVariantId   = $this->request->post['product_variant_id'] ?? false;
			$this->cart->add($productId, $quantity, $option, $productVariantId, $subscriptionPlanId);
		}

		if (!$this->cart->hasProducts()) {
			$this->redirect('cart/cart/index');

			return;
		}

		if (isset($this->request->post['login'])) {
			$this->login();

			return;
		}

		$order    = Order::getInstance();

		$checkoutInfo            = $this->session->get('checkout') ?? ['user' => $this->global['user']];
		$grandTotal              = $this->cart->getGrandTotal();
		$hasShipping             = $this->cart->hasShipping();
		$subscription            = $this->cart->getSubscription();
		$hasPayment              = ($grandTotal > 0);
		$this->view->hasShipping = $hasShipping;
		$this->view->hasPayment  = $hasPayment;

		$methods   = $this->shippingPayment($checkoutInfo, $hasShipping, $hasPayment);
		$shipping  = $methods['shipping'];
		$payment   = $methods['payment'];

		//shipping/payment totals are only added to the cart by setMethod() above,
		//so refresh the grand total, otherwise the stored order total and the
		//amount sent to the payment gateway miss shipping (and payment fees).
		$grandTotal = $this->cart->getGrandTotal();

		//a zero sub-total with a paid shipping method still requires payment
		if (!$hasPayment && $grandTotal > 0) {
			$hasPayment              = true;
			$this->view->hasPayment  = $hasPayment;
			$methods                 = $this->shippingPayment($checkoutInfo, $hasShipping, $hasPayment);
			$shipping                = $methods['shipping'] ?? $shipping;
			$payment                 = $methods['payment'] ?? $payment;
			$grandTotal              = $this->cart->getGrandTotal();
		}

		$this->data($checkoutInfo);

		if ($this->request->post) {
			if (isset($this->request->post['phone_number']) ||
				isset($this->request->post['billing_address']) ||
				isset($this->request->post['billing_address_id']) ||
				isset($this->request->post['email'])) {
				$rules = ['checkout'];

				// guest checkout
				if (!$this->global['user_id']) {
					//password is provided
					if (isset($this->request->post['register']) && $this->request->post['register'] === 'true') {
						$rules[] = 'checkout_signup';
					} else {
						$rules[] = 'guest';
					}
				} else {
					//registered user
					$rules[] = 'guest';

					foreach (['first_name', 'last_name', 'email', 'phone_number'] as $field) {
						$this->request->post[$field] ??= $this->global['user'][$field];
					}
				}

				//billing address address check
				if (empty($this->request->post['billing_address_id'])) {
					$rules[] = 'checkout_billing';

					if (isset($this->request->post['billing_address'])) {
						//if billing name is missing use account name
						$this->request->post['billing_address']['first_name'] ??= $this->request->post['first_name'] ?? '';
						$this->request->post['billing_address']['last_name'] ??= $this->request->post['last_name'] ?? '';

						$billing_address = $this->request->post['billing_address'];
						$this->request->post += prefixArrayKeys('billing_', $billing_address);
					}
				} else {
					//user address
					$rules[]            = 'checkout_billing';
					$billing_address_id = $this->request->post['billing_address_id'];
					$addressSQL         = new User_addressSQL();
					$address            = $addressSQL->get($this->global + ['user_address_id' => $billing_address_id]);

					if ($address) {
						$this->request->post['billing_address'] = $address;
					}
				}

				//if card data then validate
				if (isset($this->request->post['card'])) {
					$rules[] = 'card';
				}

				if ($hasShipping) {
					//different shipping address is selected
					if (isset($this->request->post['different_shipping_address']) && !empty($this->request->post['different_shipping_address'])) {
						$rules[] = 'checkout_shipping';

						if (isset($this->request->post['shipping_address'])) {
							$shipping_address = $this->request->post['shipping_address'];
						}
					} else {
						//use billing address as shipping address
						$rules[] = 'checkout_shipping';

						if (isset($this->request->post['billing_address'])) {
							$this->request->post['shipping_address'] = $this->request->post['billing_address'];
						}
					}
				}

				//allow only fields that are in the validator list and remove the rest
				$post         = $this->request->post;
				$validator    = new Validator($rules);

				if (!$hasShipping) {
					$validator->removeRule('shipping_method');
				}

				if (!$hasPayment) {
					$validator->removeRule('payment_method');
				}

				$checkoutInfo = $validator->filter($this->request->post) + $checkoutInfo;
				$this->session->set('checkout', $checkoutInfo);

				//nested address arrays are not validation rules so the filter drops them, preserve them from post
				foreach (['billing_address', 'shipping_address'] as $addressKey) {
					if (isset($this->request->post[$addressKey])) {
						$checkoutInfo[$addressKey] = $this->request->post[$addressKey];
					}
				}

				if (($errors = $validator->validate($this->request->post)) === true) {
					//single-use submit token: retried posts (double click, back button)
					//must not create duplicate orders. Only enforced when the theme
					//posts the field, so older themes keep working.
					if (isset($this->request->post['submit_token'])) {
						if (!$this->consumeCheckoutToken((string)$this->request->post['submit_token'])) {
							$placedOrder = $this->session->get('order');

							if (!empty($placedOrder['order_id'])) {
								//duplicate of an already placed order
								$this->redirect('checkout/confirm/index');

								return;
							}

							$this->view->errors[] = __('Invalid checkout session! Please try again.');
							$this->session->set('checkout', $checkoutInfo);
							$this->view->checkout     = $checkoutInfo;
							$this->view->submit_token = $this->rotateCheckoutToken();

							return;
						}
					}

					$this->view->submit_token        = $this->currentCheckoutToken();
					$checkoutInfo['products']        = $this->cart->getAll();
					$checkoutInfo['product_options'] = $this->cart->getProductOptions();
					$checkoutInfo['totals']          = $this->cart->getAllTotals();
					//recalculate after totals (shipping, payment fees, taxes, coupons)
					//were added, the $grandTotal captured before setMethod() is stale
					$grandTotal                      = $this->cart->getGrandTotal();
					$checkoutInfo['total']           = $grandTotal;
					//shipping address is only present for orders with shipping, billing address is always validated
					$shippingAddress = $checkoutInfo['shipping_address'] ?? [];
					$billingAddress  = $checkoutInfo['billing_address'] ?? [];
					$checkoutInfo += prefixArrayKeys('shipping_', $shippingAddress);
					$checkoutInfo += prefixArrayKeys('billing_', $billingAddress);
					$checkoutInfo += $this->global;

					//create user account if password is provided
					if (isset($checkoutInfo['password']) && !$this->global['user_id']) {
						$userInfo = [];

						foreach (['first_name', 'last_name', 'email', 'phone_number', 'password'] as $field) {
							$userInfo[$field] = $checkoutInfo[$field] ?? null;
						}

						$userInfo['display_name'] = $userInfo['first_name'] . ' ' . $userInfo['last_name'];
						$userInfo['username']     = strtolower(str_replace(' ', '', $userInfo['first_name'] . $userInfo['last_name'])) . '-' . random_int(1000, 999999);

						$result = User::add($userInfo);

						if ($result) {
							if (isset($result['user'])) {
								$checkoutInfo['user_id'] = $result['user'];
								$userInfo                = User::get(['user_id' => $checkoutInfo['user_id']]);
								//check if user was added before automatic login
								if ($userInfo) {
									\Vvveb\session(['user' => $userInfo]);
									$checkoutInfo['user']          = $userInfo;
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
						$addressSQL = new User_addressSQL();
						$address    = $addressSQL->add(['user_address' => $this->request->post['billing_address'] + ['user_id' => $user_id]]);
					}

					//if account does not have a phone number set add phone number to user profile for next order
					if ($this->global['user_id'] && !$this->global['user']['phone_number']) {
						User::update(['phone_number' => $checkoutInfo['phone_number']], ['user_id' => $this->global['user_id']]);
						User::session(['phone_number' => $checkoutInfo['phone_number']]);
					}

					if (!$checkoutInfo['user_id']) {
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

					$site = Sites::getSiteData(SITE_ID);

					$order_url = url('user/orders', [
						'host'   => $site['host'] ?? false,
						'scheme' => $_SERVER['REQUEST_SCHEME'] ?? 'http',
					]);

					$checkoutInfo['site_url']  = $site['host'];
					$checkoutInfo['site_name'] = $site['name'];

					$this->view->errors = [];

					[$checkoutInfo] = Event::trigger(__CLASS__, 'add', $checkoutInfo);

					$checkoutInfo = $order->add($checkoutInfo);

					if ($checkoutInfo && $checkoutInfo['order_id']) {
						$order_id                           = $checkoutInfo['order_id'];
						$customer_order_id                  = $checkoutInfo['customer_order_id'];
						$this->request->request['order_id'] = $order_id;
						$checkoutInfo['order_id']           = $order_id;

						$this->view->messages[] = __('Order placed!');
						$this->session->set('order', $checkoutInfo);
						$this->createPendingSubscriptions($order_id, $checkoutInfo);

						$shippingData = $checkoutInfo['shipping_data'];
						$paymentData  = $checkoutInfo['payment_data'];

						try {
							if ($hasShipping) {
								$shippingOk = $shipping->ship($checkoutInfo);

								if ($shippingOk !== true) {
									//shipping failed: don't charge, keep the cart, skip mail and confirmation
									$this->view->errors[] = $shipping->getError() ?: __('Shipping failed! Please try again or choose another shipping method.');
									$this->session->delete('order');
									$this->session->set('checkout', $checkoutInfo);
									$this->view->checkout     = $checkoutInfo;
									$this->view->submit_token = $this->currentCheckoutToken();

									return;
								}
							}

							if ($hasPayment) {
								//idempotency reference so retried submits don't double-charge
								$checkoutInfo['idempotency_key'] ??= hash('sha256', implode('|', [$order_id, $checkoutInfo['payment_method'] ?? '', number_format((float)($checkoutInfo['total'] ?? 0), 2, '.', ''), $checkoutInfo['currency'] ?? '']));
								$paymentResult   = $payment->purchase($checkoutInfo);
								$paymentRedirect = $payment->getRedirectUrl();

								if ($paymentRedirect || (is_array($paymentResult) && ($paymentResult['status'] ?? '') === \Vvveb\System\PaymentMethod::STATUS_REDIRECT)) {
									//off-site gateway: persist payment data and hand control to the gateway
									$paymentRedirect = $paymentRedirect ?: ($paymentResult['redirect'] ?? '');
									$this->session->set('order', $checkoutInfo);

									if (($shippingData !== $checkoutInfo['shipping_data']) || ($paymentData !== $checkoutInfo['payment_data'])) {
										$order->edit(['shipping_data' => $checkoutInfo['shipping_data'], 'payment_data' => $checkoutInfo['payment_data']], $order_id);
									}

									if (preg_match('#^https?://#i', $paymentRedirect)) {
										//absolute gateway URL: url() only handles internal routes
										$this->session->close();
										header("Location: $paymentRedirect");

										exit();
									}

									$this->redirect($paymentRedirect);

									return;
								}

								if ($paymentResult !== true) {
									//failed payment: stay on checkout, keep the cart, skip mail and confirmation
									$this->view->errors[] = $payment->getError() ?: __('Payment failed! Please try again or choose another payment method.');
									$this->session->delete('order');

									if (($shippingData !== $checkoutInfo['shipping_data']) || ($paymentData !== $checkoutInfo['payment_data'])) {
										$order->edit(['shipping_data' => $checkoutInfo['shipping_data'], 'payment_data' => $checkoutInfo['payment_data']], $order_id);
									}

									$this->session->set('checkout', $checkoutInfo);
									$this->view->checkout     = $checkoutInfo;
									$this->view->submit_token = $this->currentCheckoutToken();

									return;
								}

								$paymentOk = true;
							}
						} catch (\Exception $e) {
							$this->view->errors[] = $e->getMessage();
							$this->session->delete('order');
							$this->session->set('checkout', $checkoutInfo);
							$this->view->checkout     = $checkoutInfo;
							$this->view->submit_token = $this->currentCheckoutToken();

							return;
						}

						$siteSettings                             = siteSettings();
						[$checkoutInfo, $order_id, $siteSettings] = Event::trigger(__CLASS__, 'add:after', $checkoutInfo, $order_id, $siteSettings);

						if (($shippingData !== $checkoutInfo['shipping_data']) || ($paymentData !== $checkoutInfo['payment_data'])) {
							$order->edit(['shipping_data' => $checkoutInfo['shipping_data'], 'payment_data' => $checkoutInfo['payment_data']], $order_id);
						}

						try {
							$error =  __('Error sending order confirmation mail!');
							$title = sprintf(__('Order confirmation #%s'), $customer_order_id);

							\Vvveb\System\Notifications::trigger('order.new', $checkoutInfo + [
								'subject' => $title,
								'message' => $title,
							]);

							if (!email([$checkoutInfo['email'], $siteSettings['admin-email']], $title, 'order/new', $checkoutInfo + ['title' => $title])) {
								$this->session->set('errors', $error);
								$this->view->errors[] = $error;
							}
						} catch (\Exception $e) {
							$error = __('Error sending order confirmation mail!');

							if (defined('DEBUG') && DEBUG) {
								$error .= "\n" . $e->getMessage();
							}
							$this->session->set('errors', $error);
							$this->view->errors[] = $error;
						}

						// check low stock threshold for each ordered product
						foreach ($checkoutInfo['products'] ?? [] as $product) {
							try {
								if (!empty($product['product_id'])) {
									\Vvveb\System\Notifications::checkLowStock([
										'product_id' => $product['product_id'],
										'name'       => $product['name'] ?? '',
										'quantity'   => $product['stock_quantity'] ?? $product['quantity'] ?? 0,
									]);
								}
							} catch (\Throwable $e) {
							}
						}

						$this->cart->empty();

						// clear notifications cache
						CacheManager::clearObjectCache('component', 'notifications');

						$this->redirect('checkout/confirm/index');

						return;
					} else {
						$this->view->errors[] = __('Error creating checkout!');
					}
				} else {
					$this->view->errors = $errors;
				}
			}

			$this->session->set('checkout', $checkoutInfo);
		}

		$this->view->checkout     = $checkoutInfo;
		$this->view->submit_token = $this->currentCheckoutToken();
	}
}
