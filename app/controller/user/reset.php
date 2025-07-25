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
use function Vvveb\email;
use Vvveb\System\Event;
use Vvveb\System\Functions\Str;
use Vvveb\System\Sites;
use Vvveb\System\User\User;
use function Vvveb\url;

#[\AllowDynamicProperties]
class Reset extends Base {
	function reset() {
		$token           = $this->request->get['token'] ?? false;
		$user            = $this->request->get['user'] ?? false;
		$password        = $this->request->post['password'] ?? false;
		$confirmPassword = $this->request->post['confirm_password'] ?? false;
		$admin           = [];

		if ($user && $token) {
			$admin = User::get(['user' => $user, 'token' => $token]);

			if ($password) {
				if ($confirmPassword && ($password === $confirmPassword)) {
					if ($admin) {
						if (User::update(['token' => '', 'password' => $password], ['username' => $user, 'token' => $token])) {
							$success                      = __('Password was reset!');
							$this->view->success['login'] = $success;
							$this->session->set('success', ['login' => $success]);
							$this->redirect('/user/login');
						//header('Location: ' . url(['module' => 'user/login', 'success' => $success]));
						} else {
							$errors                      =  __('Update failed!');
							$this->view->errors['login'] = $errors;
							$this->session->set('errors', ['login' => $errors]);
						}
					}
				} else {
					$errors                      =  __('Passwords don\'t match!');
					$this->view->errors['login'] = $errors;
				}
			}
		}

		if (! $admin) {
			die(__('Invalid or expired token!'));
		}
	}

	function index() {
		$email      = $this->request->post['email'] ?? false;
		$loginData  = [];

		if ($email) {
			$loginData['email'] = $email;

			list($loginData) = Event :: trigger(__CLASS__, __FUNCTION__ , $loginData);

			if ($loginData) {
				if (($adminData = User::get($loginData)) != false) {
					//set reset token and send reset email
					$token = Str::random(32);
					User::update(['token' => $token], ['email' => $adminData['email']]);

					$agent = $_SERVER['HTTP_USER_AGENT'];

					if (strpos($agent, 'Linux') !== false) {
						$os = 'Linux';
					} elseif (strpos($agent, 'Win') !== false) {
						$os = 'Windows';
					} elseif (strpos($agent, 'Mac') !== false) {
						$os = 'Mac';
					} else {
						$os = 'UnKnown';
					}

					$site = Sites :: getSiteData();

					$reset_url = url('user/reset/reset', [
						'token'  => $token,
						'user'   => $adminData['username'],
						'host'   => $site['host'] ?? false,
						'scheme' => $_SERVER['REQUEST_SCHEME'] ?? 'http',
					]);

					$data = $adminData + [
						'token'            => $token,
						'operating_system' => $os,
						'browser_name'     => $_SERVER['HTTP_USER_AGENT'],
						'reset_url'        => $reset_url,
					];

					if (email($adminData['email'], __('Password reset'),'user/reset', $data)) {
						$success               = __('A reset email was sent, please use it to reset your password!');
						$this->view->success[] = $success;
						$this->session->set('success', ['login' => $success]);
						$this->redirect('/user/login');
					} else {
						$this->view->errors[] = __('Error sending reset email!');
					}
				} else {
					$this->view->errors['login'] = __('Email not found!');
				}
			}
		}
	}
}
