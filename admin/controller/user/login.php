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

use \Vvveb\System\Functions\Str;
use function Vvveb\__;
use function Vvveb\setLanguage;
use Vvveb\System\User\Admin;
use Vvveb\System\Validator;

#[\AllowDynamicProperties]
class Login {
	protected function redirect($url = '/', $parameters = []) {
		$redirect = \Vvveb\url($url, $parameters);

		if ($redirect) {
			$url = $redirect;
		}

		//$this->session->close();

		return header("Location: $url");
	}

	function index() {
		if (isset($this->request->post['logout'])) {
			return Admin::logout();
		}

		//$this->checkAlreadyLoggedIn();
		$view 	 	   = $this->view;
		$admin      = Admin::current();
		$admin_path = \Vvveb\adminPath();

		if ($admin) {
			return $this->redirect($admin_path);
		}

		$this->view->action = "$admin_path/?module=user/login";
		$this->view->modal  = $this->request->get['modal'] ?? false;

		//$this->session = Session::getInstance();
		$language = $this->session->get('language') ?? 'en_US';
		setLanguage($language);

		if ($errors = $this->session->get('errors')) {
			$view->errors[] = $errors;
			$this->session->delete('errors');
		}

		if ($success = $this->session->get('success')) {
			$view->success[] = $success;
			$this->session->delete('success');
		}

		$validator = new Validator(['login']);

		if (isset($this->request->get['module']) && $this->request->get['module'] != 'user/login') {
			$this->view->redir = $this->request->get['module'];
		}

		if (($this->request->method == 'POST') &&
			($this->view->errors = $validator->validate($this->request->post)) === true) {
			$user	    = $this->request->post['user'];
			$safemode = $this->request->post['safemode'] ?? false;
			$flags    = [];

			if (strpos($user, '@')) {
				$loginData['email'] = $user;
			} else {
				$loginData['user'] = $user;
			}

			$loginData['password'] = $this->request->post['password'];

			if ($safemode) {
				$flags['safemode'] = true;
			}

			if ($userInfo = Admin::login($loginData, $flags)) {
				$this->view->success[] = __('Login successful!');

				if (isset($this->request->post['redir']) && $this->request->post['redir'] && $_SERVER['REQUEST_URI'] != $this->request->post['redir']) {
					$url = parse_url($this->request->post['redir']);
					$this->redirect($url['path'] . '?' . ($url['query'] ?? '') . '#' . ($url['fragment'] ?? ''));
				    //$this->redirect($this->request->post['redirect']);
				} else {
					$this->redirect($admin_path);
				}
			} else {
				//user not found or wrong password
				$this->view->errors = [__('Authentication failed, wrong email or password!')];
				$this->session->set('csrf', Str::random());
			}
		} else {
			//return $this->redirect($admin_path);
			$this->session->set('csrf', Str::random());
		}
	}
}
