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

namespace Vvveb\Controller\Order;

use function Vvveb\__;
use Vvveb\Controller\Base;
use Vvveb\Controller\Content\AutocompleteTrait;
use function Vvveb\email;
use function Vvveb\humanReadable;
use function Vvveb\orderStatusBadgeClass;
use function Vvveb\paymentStatusBadgeClass;
use function Vvveb\shippingStatusBadgeClass;
use function Vvveb\siteSettings;
use Vvveb\Sql\Order_logSQL;
use Vvveb\Sql\OrderSQL;
use Vvveb\System\Cart\Cart;
use Vvveb\System\Cart\Currency;
use Vvveb\System\Cart\Order as SystemOrder;
use Vvveb\System\Cart\OrderCart;
use Vvveb\System\Core\View;
use Vvveb\System\Images;
use Vvveb\System\Payment;
use Vvveb\System\Shipping;
use Vvveb\System\Sites;
use Vvveb\System\Validator;
use function Vvveb\url;

class Order extends Base {
	use AutocompleteTrait;

	protected string $type = 'order';

	/**
	 * Display the order edit form or order details.
	 *
	 * @return void
	 */
	public function index(): void {
		$site    = siteSettings($this->global['site_id']);
		$options = array_intersect_key(
			$site,
			array_flip(['weight_type_id', 'length_type_id', 'currency_id', 'country_id'])
		);

		$cart     = Cart::getInstance($options);
		$payment  = Payment::getInstance();
		$shipping = Shipping::getInstance();
		$view     = $this->view;
		$order    = [];

		if (isset($this->request->get['order_id'])) {
			$options = ['order_id' => (int)$this->request->get['order_id'], 'type' => $this->type] + $this->global;

			$orders   = new OrderSQL();
			$results  = $orders->get($options);

			if ($results['order']) {
				$currency = Currency::getInstance();

				if (isset($results['products'])) {
					foreach ($results['products'] as $id => &$product) {
						$product['url'] = htmlspecialchars(\Vvveb\url('product/product/index', $product));

						if (isset($product['images'])) {
							$product['images'] = json_decode($product['images'], true);

							foreach ($product['images'] as &$image) {
								$image['image'] = Images::image($image['image'], 'product');
							}
						}

						if (isset($product['option_value'])) {
							$product['option_value'] = json_decode($product['option_value'], true);

							foreach ($product['option_value'] as &$option) {
								if (isset($option['price'])) {
									$option['price_formatted'] = $currency->format($option['price']);
								}
							}
						}

						if (isset($product['image']) && $product['image']) {
							$product['image'] = Images::image($product['image'], 'product');
						}

						$product['price_formatted'] = $currency->format($product['price']);
						$product['tax_formatted']   = $currency->format($product['tax']);
					}
				}

				foreach ($results['total'] as $id => &$total) {
					$total['value_formatted'] = $currency->format($total['value']);
				}

				$order                    = &$results['order'];
				$order['user_url']        = \Vvveb\url(['module' => 'user/user', 'user_id' => $order['user_id']]);
				$order['total_formatted'] = $currency->format($order['total']);
				$shippingData             = json_decode($order['shipping_data'] ?? '', true);
				$paymentData              = json_decode($order['payment_data'] ?? '', true);

				if (!is_array($shippingData)) {
					$shippingData = [];
				}

				if (!is_array($paymentData)) {
					$paymentData = [];
				}

				$order['shipping_data'] = $shippingData;
				$order['payment_data']  = $paymentData;

				//human readable method titles, falling back to the stored method
				//key (minus the -index suffix) when the gateway did not record one
				$shippingMethod                = preg_replace('/-\d+$/', '', (string)($order['shipping_method'] ?? ''));
				$paymentMethod                 = preg_replace('/-\d+$/', '', (string)($order['payment_method'] ?? ''));
				$order['shipping_title']       = $shippingData['title'] ?? $shippingData['gateway'] ?? ($shippingMethod ? humanReadable($shippingMethod) : '');
				$order['payment_title']        = $paymentData['title'] ?? $paymentData['gateway'] ?? ($paymentMethod ? humanReadable($paymentMethod) : '');
				$order['shipping_description'] = $shippingData['description'] ?? $shippingData['text'] ?? '';
				$order['payment_description']  = $paymentData['description'] ?? $paymentData['text'] ?? '';
				//legacy alias, the customer note column is `notes`
				$order['comment']         = $order['notes'] ?? '';
				$order['class']           = orderStatusBadgeClass($order['order_status_id']);
				$order['payment_class']   = paymentStatusBadgeClass($order['payment_status_id']);
				$order['shipping_class']  = shippingStatusBadgeClass($order['shipping_status_id']);

				$data = $orders->getData($order);

				$view->set($data);
				$view->set($results);

				$url                    = ['module' => 'order/order', 'action' => 'print', 'order_id' => $order['order_id']];
				$view->printUrl 	       = \Vvveb\url($url);
				$view->printShippingUrl = \Vvveb\url(['action' => 'printShipping'] + $url);
				$view->printInvoiceUrl  = \Vvveb\url(['action' => 'printInvoice'] + $url);
			} else {
				$this->notFound(__('Order not found!'));
			}
		} else {
			//new order
			$orders   = new OrderSQL();
			$data     = $orders->getData($this->global);
			$view->set($data);
		}

		$this->view->order_payment     = $payment->getMethods($order);
		$this->view->order_shipping    = $shipping->getMethods($order);
		$this->view->transactions      = \Vvveb\System\PaymentLedger::forOrder($order['order_id'] ?? 0);
		$this->view->remaining_capture = $this->remainingAmount((int)($order['order_id'] ?? 0), 'capture', (float)($order['total'] ?? 0));
		$this->view->remaining_refund  = $this->remainingAmount((int)($order['order_id'] ?? 0), 'refund', (float)($order['total'] ?? 0));

		$paymentMethod = $order['payment_method'] ?? false;
		$instance      = $paymentMethod ? $payment->getMethodInstance($paymentMethod) : null;
		//if payment method configuration has been deleted use payment plugin directly
		if (!$instance && isset($order['payment_data']['class'])) {
			$class    = $order['payment_data']['class'];
			$instance = new $class($cart);
		}

		$this->view->can_capture = (bool)($instance && $instance->supports('capture'));
		$this->view->can_refund  = (bool)($instance && $instance->supports('refund'));
		$this->view->can_void    = (bool)($instance && $instance->supports('void'));
	}

	/**
	 * Execute a capture, refund or void action on the order's payment method.
	 *
	 * @param string $action     Payment method action to invoke (capture/refund/void).
	 * @param string $successMsg Success message shown to the admin.
	 * @param string $errorMsg   Error message shown to the admin.
	 *
	 * @return void
	 */
	private function paymentAction(string $action, string $successMsg, string $errorMsg): void {
		$order_id = $this->request->post['order_id'] ?? false;

		if (!$order_id) {
			$this->view->errors[] = __('Order id missing!');

			$this->index();

			return;
		}

		$orders  = new OrderSQL();
		$results = $orders->get(['order_id' => (int)$order_id, 'type' => $this->type] + $this->global);

		if (!isset($results['order']) || !$results['order']) {
			$this->view->errors[] = __('Order not found!');

			$this->index();

			return;
		}

		$order       = $results['order'];
		$method      = $order['payment_method'] ?? false;
		$paymentData = json_decode($order['payment_data'] ?? '', true) ?: [];
		$payment     = Payment::getInstance();
		$instance    = $method ? $payment->getMethodInstance($method) : null;

		if (!$instance || !$instance->supports($action)) {
			$this->view->errors[] = __('Payment method does not support this action!');

			$this->index();

			return;
		}

		//optional partial amount, empty means the full remaining total
		$amount    = null;
		$rawAmount = $this->request->post['amount'] ?? null;

		if ($rawAmount !== null && $rawAmount !== '') {
			if (!is_numeric($rawAmount) || (float)$rawAmount <= 0) {
				$this->view->errors[] = __('Amount must be a positive number!');

				$this->index();

				return;
			}

			$amount = round((float)$rawAmount, 2);
		}

		if (in_array($action, ['capture', 'refund'], true)) {
			$remaining = $this->remainingAmount((int)$order_id, $action, (float)($order['total'] ?? 0));

			if ($amount !== null && $amount - $remaining > 0.005) {
				$this->view->errors[] = sprintf(__('Amount exceeds the remaining %s!'), $remaining);

				$this->index();

				return;
			}
		}

		$checkoutInfo = [
			'order_id' => (int)$order_id,
			'total'    => $order['total'] ?? 0,
			'currency' => $order['currency'] ?? '',
		] + $paymentData;

		if ($amount !== null) {
			$checkoutInfo['amount'] = $amount;
		}

		if ($instance->$action($checkoutInfo)) {
			$this->view->success[] = $successMsg;
		} else {
			$this->view->errors[] = $instance->getError() ?: $errorMsg;
		}

		$this->index();
	}

	/**
	 * Remaining capturable/refundable total from the money ledger.
	 *
	 * Capture draws on authorized (minus voided and captured) rows, refund on
	 * captured plus purchased (minus refunded) rows. Falls back to the order
	 * total when the ledger has no usable rows yet.
	 *
	 * @param int    $orderId Order id.
	 * @param string $action  `capture` or `refund`.
	 * @param float  $total   Order total fallback.
	 *
	 * @return float Remaining amount, never negative.
	 */
	private function remainingAmount(int $orderId, string $action, float $total): float {
		try {
			$rows = \Vvveb\System\PaymentLedger::forOrder($orderId);
		} catch (\Throwable $e) {
			return $total;
		}

		if (!$rows) {
			return $total;
		}

		$sum = function(array $kinds) use ($rows): float {
			$sum = 0.0;

			foreach ($rows as $row) {
				if (($row['status'] ?? '') === 'succeeded' && in_array($row['kind'] ?? '', $kinds, true)) {
					$sum += (float)($row['amount'] ?? 0);
				}
			}

			return $sum;
		};

		if ($action === 'capture') {
			$authorized = $sum(['authorize']) - $sum(['void']);

			if ($authorized <= 0) {
				return $total;
			}

			return max(0.0, round($authorized - $sum(['capture']), 2));
		}

		$captured = $sum(['capture']) + $sum(['purchase']);

		if ($captured <= 0) {
			return $total;
		}

		return max(0.0, round($captured - $sum(['refund']), 2));
	}

	/**
	 * Capture a previously authorized order payment through the payment method.
	 *
	 * @return void
	 */
	public function capture(): void {
		$this->paymentAction('capture', __('Payment captured!'), __('Payment capture failed!'));
	}

	/**
	 * Refund the order payment through the payment method.
	 *
	 * @return void
	 */
	public function refund(): void {
		$this->paymentAction('refund', __('Payment refunded!'), __('Payment refund failed!'));
	}

	/**
	 * Void/cancel the order payment through the payment method.
	 *
	 * @return void
	 */
	public function void(): void {
		$this->paymentAction('void', __('Payment canceled!'), __('Payment cancel failed!'));
	}

	/**
	 * Print the order.
	 *
	 * @return void
	 */
	public function print(): void {
		$this->index();
	}

	/**
	 * Print the shipping details.
	 *
	 * @return void
	 */
	public function printShipping(): void {
		$this->index();
	}

	/**
	 * Print the invoice.
	 *
	 * @return void
	 */
	public function printInvoice(): void {
		$this->index();
	}

	/**
	 * Add a product to the order cart.
	 *
	 * @return void
	 */
	public function cart(): void {
		$order_id   = $this->request->get['order_id'] ?? false;
		$product_id = $this->request->post['product_id'] ?? false;
		$quantity   = $this->request->post['quantity'] ?? 1;
		$name       = $this->request->post['name'] ?? '';
		$model      = $this->request->post['model'] ?? '';
		$price      = $this->request->post['price'] ?? 1;

		if ($order_id && $product_id) {
			$product  = compact('product_id', 'quantity', 'name', 'price', 'model');

			$site    = siteSettings($this->global['site_id']);
			$options = $this->global + $site;
			unset($options['admin_id']);

			$order = new OrderCart($order_id, $options);
			$order->addOrderProduct($product_id, false, $quantity, []);
			$order->updateCart();
			$order->write();
		}

		$this->index();
	}

	/**
	 * Send an email notification about an order update.
	 *
	 * @param int|string $order_id          The internal order ID.
	 * @param string     $customer_order_id The customer-facing order ID.
	 * @param string     $email             The recipient email address.
	 * @param string     $message           The notification message.
	 *
	 * @return void
	 */
	private function sendNotification(int|string $order_id, string $customer_order_id, string $email, string $message): void {
		$error = __('Error sending order confirmation mail!');

		try {
			$title = sprintf(__('Order update #%s'), $customer_order_id);

			$options = ['order_id' => $order_id] + $this->global;

			$orders   = new OrderSQL();
			$results  = $orders->get($options);

			\Vvveb\System\Notifications::trigger('order.status_change', ['message' => $message, 'title' => $title] + ($results ?? []) + $this->global + [
				'subject' => $title,
			]);

			/*
						if (! email([$email], $title, 'order/update', ['message' => $message, 'title' => $title] + $results + $this->global, [], 'app')) {
							$this->session->set('errors', $error);
							$this->view->errors[] = $error;
						}
			*/
		} catch (\Exception $e) {
			if (DEBUG) {
				$error .= "\n" . $e->getMessage();
			}
			$this->session->set('errors', $error);
			$this->view->errors[] = $error;
		}
	}

	/**
	 * Save an order log entry and optionally send a notification.
	 *
	 * @return void
	 */
	public function saveLog(): void {
		$order_id          = $this->request->get['order_id'] ?? false;
		$customer_order_id = $this->request->post['customer_order_id'] ?? false;
		$log               = $this->request->post['log'] ?? [];
		$notify            = $log['notify'] ?? false;
		$public            = $log['public'] ?? false;
		$view              = $this->view;

		$this->index();

		if ($order_id && $log) {
			// update order status with the last log status
			$order  = new OrderSQL();
			$result = $order->edit(['order_id' => $order_id, 'order' => ['order_status_id' => $log['order_status_id']]]);

			if (isset($result['order'])/* && $result['order']*/) {
				$orderLog = new Order_logSQL();
				$result   = $orderLog->add(['order_id' => $order_id, 'order_log' => $log]);

				if (isset($result['order_log']) && $result['order_log']) {
					$view->success = [__('Order status saved!')];

					$this->view->log[] = $log;

					if ($notify) {
						$message = '';

						if ($public) {
							$message = $log['note'];
						}

						$email = $this->view->order['email'];
						$this->sendNotification($order_id, $customer_order_id, $email, $message);
					}
				} else {
					$view->errors[] = $orderLog->error;
				}
			} else {
				$view->errors[] = $order->error;
			}
		}
	}

	/**
	 * Save the order from the request data.
	 *
	 * @return void
	 */
	public function save(): void {
		$validator = new Validator(['checkout', 'order', 'checkout_shipping', 'checkout_billing']);
		$view      = View::getInstance();
		$order_id  = $this->request->get['order_id'] ?? false;
		$order     = $this->request->post ?? [];

		$site = Sites::getSiteData($this->global['site_id']);

		$order_url = url('user/orders', [
			'host'   => $site['host'] ?? false,
			'scheme' => $_SERVER['REQUEST_SCHEME'] ?? 'http',
		]);

		$order['site_url']   = $site['host'];
		$order['site_name']  = $site['name'];
		$order['products']   = [];
		$order['totals']     = [];

		if (($errors = $validator->validate($order)) === true) {
			$orders = new OrderSQL();

			if ($order_id) {
				$result = $orders->edit(['order_id' => $order_id, 'order' => $order]);

				if ($result >= 0) {
					$view->success = [__('Order saved')];
				} else {
					$view->errors = [$orders->error];
				}
			} else {
				$systemOrder = SystemOrder::getInstance();
				$order       = $systemOrder->add($order + $this->global);
				$order_id    = $order['order_id'];

				if (isset($order_id)) {
					$message                    = __('Order saved!');
					$this->view->success['get'] = $message;
					$url                        = ['module' => 'order/order', 'order_id' => $order_id, 'success' => $message];
					$this->redirect($url);
				} else {
					$view->errors = [$orders->error];
				}
			}
		} else {
			$view->errors = $errors;
		}

		$this->index();
	}
}
