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
use Vvveb\Sql\Admin_Failed_LoginSQL;
use Vvveb\System\Event;
use Vvveb\System\User\Admin;
use Vvveb\System\Validator;

#[\AllowDynamicProperties]
class Login {
	//failed attemps per minute Y-m-d H:i:00
	protected $failedTimeInterval = 'Y-m-d H:00:00'; //failed attemps per hour

	protected $failedCount = 10; //the number of failures before account is locked

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
		$view       = $this->view;
		$admin      = Admin::current();
		$admin_path = \Vvveb\adminPath();

		if ($admin) {
			return $this->redirect($admin_path);
		}

		$this->view->adminPath = $admin_path;
		$this->view->action    = $admin_path . 'index.php?module=user/login';
		$this->view->modal     = $this->request->get['modal'] ?? false;

		//$this->session = Session::getInstance();
		$language = $this->session->get('language') ?? 'en_US';
		setLanguage($language);

		if (isset($this->request->get['success'])) {
			$view->success['get'] = htmlentities($this->request->get['success']);
		}

		if (isset($this->request->get['errors'])) {
			$view->errors['get'] = htmlentities($this->request->get['errors']);
		}

		if ($errors = $this->session->get('errors')) {
			if (is_array($errors)) {
				$view->errors = ($view->errors ?? []) + $errors;
			} else {
				$view->errors['session'] = $errors;
			}
			$this->session->delete('errors');
		}

		if ($success = $this->session->get('success')) {
			if (is_array($success)) {
				$view->success = ($view->success ?? []) + $success;
			} else {
				$view->success['session'] = $success;
			}
			$this->session->delete('success');
		}

		$validator = new Validator(['login']);

		if (isset($this->request->get['module']) && $this->request->get['module'] != 'user/login') {
			$this->view->redir = $this->request->get['module'];
		}

		$method = $this->request->getMethod();

		if (($method == 'post') &&
			($this->view->errors = $validator->validate($this->request->post)) === true) {
			$user	    = $this->request->post['user'];

			$safemode = $this->request->post['safemode'] ?? false;
			$flags    = [];

			if (strpos($user, '@')) {
				$loginData['email'] = $user;
			} else {
				$loginData['username'] = $user;
			}

			$failedLogin   = new Admin_Failed_LoginSQL();
			$date          = date($this->failedTimeInterval);
			$lastIp        = $_SERVER['REMOTE_ADDR'] ?? '';
			$failedAttemps = $failedLogin->get(['updated_at' => $date, 'status' => 1] + $loginData);

			if ($failedAttemps && ($failedAttemps['count'] > $this->failedCount)) {
				$this->view->errors = [__('Too many login attempts, try again in one hour!')];
				$failedLogin->logFailed(['last_ip' => $lastIp, 'updated_at' => $date] + $loginData);

				return;
			}

			$loginData['password'] = $this->request->post['password'];

			if ($safemode) {
				$flags['safemode'] = true;
			}

			list($loginData) = Event :: trigger(__CLASS__, __FUNCTION__ , $loginData);

			if ($loginData) {
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
					//increment failed attempts
					$failedLogin->logFailed(['last_ip' => $lastIp, 'updated_at' => $date] + $loginData);
				}
			}
		} else {
			//return $this->redirect($admin_path);
			$this->session->set('csrf', Str::random());
		}
	}
}
