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

class Login extends \Vvveb\Controller\Base {
	use LoginTrait;

	function index() {
		$view = $this->view;

		if ($errors = $this->session->get('errors')) {
			$view->errors['login'] = $errors;
			$this->session->delete('errors');
		}

		if ($success = $this->session->get('success')) {
			$view->success['login'] = $success;
			$this->session->delete('success');
		}

		return $this->login();
	}
}
