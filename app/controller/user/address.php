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
use Vvveb\Sql\User_AddressSQL;

class Address extends Base {
	function index() {
	}

	function edit() {
		$user_address_id = $this->request->get['user_address_id'] ?? false;
		$user_address    = [];

		$addressModel = new User_AddressSQL();

		if (isset($this->request->post['user_address'])) {
			$user_address            = $this->request->post['user_address'];
			$user_address['user_id'] = $this->global['user_id'];
			$options                 = ['user_address' => $user_address] + $this->global;

			if ($user_address_id) {
				$options['user_address_id'] = $user_address_id;
				$result                     = $addressModel->edit($options);
			} else {
				$result = $addressModel->add($options);
			}

			if (! $result) {
				$this->view->errors = [$addressModel->error];
			} else {
				$message               =  __('Address saved!');
				$this->view->success[] = $message;
			}
		}

		if ($user_address_id) {
			$options      = ['user_address_id' => $user_address_id] + $this->global;
			$user_address = $addressModel->get($options);
		}

		$this->view->user_address_id = $user_address_id;
		$this->view->user_address 	  = $user_address;
	}
}
