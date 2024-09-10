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

use function Vvveb\siteSettings;
use Vvveb\Sql\OrderSQL;

class Order {
	private $model;

	public static function getInstance($options = []) {
		static $inst = null;

		if ($inst === null) {
			$inst = new Order($options);
		}

		return $inst;
	}

	public function __construct() {
		$this->model = new OrderSQL();
	}

	public function getData() {
		return $this->model->getData();
	}

	public function get($order_id) {
		return $this->model->get(['order_id' => $order_id]);
	}

	public function edit($data, $order_id) {
		return $this->model->edit(['order' => $data, 'order_id' => $order_id]);
	}

	public function add($data) {
		//set defaults
		$defaults = ['invoice_format', 'order_id_format', 'order_status_id', 'remote_ip', 'forwarded_for_ip'];

		$site                     = siteSettings();
		$site['remote_ip']        = $_SERVER['REMOTE_ADDR'] ?? false;
		$site['forwarded_for_ip'] = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? false;

		foreach ($defaults as $default) {
			if (
				(! isset($data) || ! empty($data)) &&
				(isset($site[$default]) && ! empty($site[$default]))
			) {
				$data[$default] = $site[$default];
			}
		}

		$data['invoice_no']        = \Vvveb\invoiceFormat($data['invoice_format'], $data);
		$data['customer_order_id'] = \Vvveb\invoiceFormat($data['order_id_format'], $data);
		//todo: check if email is already registerd for new accounts

		//add products one by one to set product options for each
		$products         = $data['products'];
		$data['products'] = [];

		$result   = $this->model->add(['order' => $data]);
		$order_id = $result['order'] ?? false;

		if ($order_id) {
			//add products
			foreach ($products as $key => $product) {
				$product_options               = $product['option_value'] ?? [];
				$this->model->addProduct(['product' => $product, 'product_options' => $product_options, 'order_id' => $order_id]);
			}
			//update invoice to set {order_id} variable
			$data['order_id']          = $order_id;
			$data['user_id']           = $data['user_id'] ?? 0;
			$data['customer_order_id'] = \Vvveb\invoiceFormat($data['order_id_format'], $data);
			$data['invoice_no']        = \Vvveb\invoiceFormat($data['invoice_format'], $data);
			$result                    = $this->model->edit(['order' => ['invoice_no' => $data['invoice_no'], 'customer_order_id' => $data['customer_order_id']], 'order_id' => $order_id]);
		}

		return $data;
	}
}
