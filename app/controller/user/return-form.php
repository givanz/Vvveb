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

use function Vvveb\__;
use Vvveb\Controller\Base;
use function Vvveb\session as sess;
use Vvveb\Sql\OrderSQL;
use Vvveb\Sql\Return_ReasonSQL;
use Vvveb\Sql\ReturnSQL;

class ReturnForm extends Base {
	function save() {
		$customer_order_id      = $this->request->post['customer_order_id'] ?? false;
		$email                  = $this->request->post['email'] ?? '';
		$csrf                   = $this->request->post['csrf'] ?? false;
		$validCsrf              = sess('csrf');
		$this->view->validOrder = false;

		if ($validCsrf === $csrf) {
			if ($customer_order_id && $email) {
				$orders  = new OrderSQL();
				$results = $orders->get(['customer_order_id' => $customer_order_id, 'email' => $email]);

				if ($results && $results['order']) {
					$this->request->post['order_id']             = $results['order']['order_id'];
					$this->request->post['return_resolution_id'] =  1;
					$this->request->post['return_status_id']     =  1;
					$this->request->post['product_id']           =  1;
				} else {
					$this->view->errors['return'] = sprintf(__('Order %s not found!'), $customer_order_id);

					return $this->index();
				}

				$data    = $this->request->post + $this->global;
				$return  = new ReturnSQL();
				$results = $return->add(['return' => $data]);

				if ($results && $results['return']) {
					$this->view->success['return'] = __('Return submitted!');
					$this->view->validOrder        = true;
				} else {
					$this->view->errors['return'] = __('Error submitting return!');
				}
			} else {
				$this->view->errors['return'] = __('Order id and email not provided!');
			}
		} else {
			$this->view->errors[] = __('Invalid request!');
		}

		return $this->index();
	}

	function index() {
		$reason  = new Return_ReasonSQL();
		$reasons = $reason->getAll($this->global + ['start' => 0, 'limit' => 100])['return_reason'] ?? [];

		$this->view->return_reason_id = $reasons;
	}
}
