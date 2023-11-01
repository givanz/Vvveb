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
use Vvveb\System\User\User;
use Vvveb\System\Validator;

trait LoginTrait {
	function login() {
		if (isset($this->request->post['logout'])) {
			$success             = __('Logout successful!');
			$this->view->success = [$success];

			return User::logout();
		}

		$validator = new Validator(['login']);

		if ($this->request->post) {
			if (($errors['login'] = $validator->validate($this->request->post)) === true) {
				if ($userInfo = User::login($this->request->post)) {
					$success = __('Login successful!');
					$this->session->set('success', ['login' => $success]);
					$this->session->close();
					$this->view->success['login']         = $success;
					$this->view->global['user_id']        = $userInfo['user_id'];
					$this->redirect('/user');
				} else {
					//user not found or wrong password
					$this->view->errors['login'] = __('Authentication failed, wrong email or password!');
				}
			} else {
				$this->view->errors = $errors;
			}
		}
	}
}
