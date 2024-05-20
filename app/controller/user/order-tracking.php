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

namespace Vvveb\Controller\User;

use function \Vvveb\__;
use function \Vvveb\session as sess;
use \Vvveb\Sql\OrderSQL;

class OrderTracking extends \Vvveb\Controller\Base {
	function order() {
		$customer_order_id      = trim($this->request->post['customer_order_id'] ?? '');
		$email                  = trim($this->request->post['email'] ?? '');
		$csrf                   = $this->request->post['csrf'] ?? false;
		$validCsrf              = sess('csrf');
		$this->view->validOrder = false;

		if ($validCsrf === $csrf) {
			if ($customer_order_id && $email) {
				$orders  = new OrderSQL();
				$results = $orders->get(['customer_order_id' => $customer_order_id, 'email' => $email]);

				if ($results && $results['order']) {
					$this->view->success[]  = __('Order found!');
					$this->view->validOrder = true;
				} else {
					$this->view->errors[] = __('Order not found!');
				}
			} else {
				$this->view->errors[] = __('Order id and email not provided!');
			}
		} else {
			$this->view->errors[] = __('Invalid request!');
		}

		return $this->index();
	}

	function index() {
	}
}
