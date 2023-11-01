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
use function Vvveb\orderStatusBadgeClass;
use function Vvveb\prefixArrayKeys;
use Vvveb\Sql\OrderSQL;
use Vvveb\System\Cart\Currency;
use Vvveb\System\Core\View;
use Vvveb\System\Images;
use Vvveb\System\Payment;
use Vvveb\System\Shipping;
use Vvveb\System\Sites;
use Vvveb\System\Validator;
use function Vvveb\url;

class Order extends Base {
	protected $type = 'order';

	function index() {
		$view = View :: getInstance();

		$payment  = Payment::getInstance();
		$shipping = Shipping::getInstance();

		$this->view->order_payment  = $payment->getMethods();
		$this->view->order_shipping = $shipping->getMethods();

		if (isset($this->request->get['order_id'])) {
			$options = ['order_id' => (int)$this->request->get['order_id'], 'type' => $this->type] + $this->global;

			$orders   = new OrderSQL();
			$results  = $orders->get($options);

			if ($results['order']) {
				$currency = Currency::getInstance();

				if (isset($results['products'])) {
					foreach ($results['products'] as $id => &$product) {
						$product['url'] = htmlentities(\Vvveb\url('product/product/index', $product));

						if (isset($product['images'])) {
							$product['images'] = json_decode($product['images'], true);

							foreach ($product['images'] as &$image) {
								$image['image'] = Images::image($image['image'], 'product');
							}
						}

						if (isset($product['image']) && $product['image']) {
							$product['image'] =Images::image($product['image'], 'product');
							//$product['images'][] = ['image' => Images::image($product['image'], 'product')];
						}

						$product['tax_formatted'] = $currency->format($product['tax'], '$');
					}
				}

				foreach ($results['total'] as $id => &$total) {
					$total['value_formatted'] = $currency->format($total['value'], '$');
				}

				$order                    = &$results['order'];
				$order['total_formatted'] = $currency->format($order['total'], '$');
				$order['shipping_data']   = json_decode($order['shipping_data'] ?? '', true);
				$order['payment_data']    = json_decode($order['payment_data'] ?? '', true);
				$order['class'] 		        = orderStatusBadgeClass($order['order_status_id']);

				$order += prefixArrayKeys('shipping_', $order['shipping_data']) ?? [];
				$order += prefixArrayKeys('payment_', $order['payment_data']) ?? [];

				$data     		      = $orders->getData($order);
				$view->set($data);
				$view->set($results);

				$url                    = ['module' => 'order/order', 'action' => 'print', 'order_id' => $order['order_id']];
				$view->printUrl 	       = \Vvveb\url($url);
				$view->printShippingUrl = \Vvveb\url(['action' => 'printShipping'] + $url);
			} else {
				return $this->notFound(true, __('Order not found!'));
			}
		} else {
			//new order
			$orders   = new OrderSQL();
			$data     = $orders->getData($this->global);
			$view->set($data);
		}
	}

	function print() {
		return $this->index();
	}

	function printShipping() {
		return $this->index();
	}

	function save() {
		$validator = new Validator(['order']);
		$view      = view :: getInstance();
		$order_id  = $this->request->get['order_id'] ?? false;
		$order     = $this->request->post ?? [];

		$site = Sites :: getSiteData();

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
				$result               = $orders->edit(['order_id' => $order_id, 'order' => $order]);

				if ($result >= 0) {
					$view->success = [__('Order saved')];
				} else {
					$view->errors = [$orders->error];
				}
			} else {
				$result = $orders->add(['order' => $order + $this->global]);

				if (isset($result['order'])) {
					$view->success = __('Order saved!');
					$url           = ['module' => 'order/order', 'order_id' => $result['order']];
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
