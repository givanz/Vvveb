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
use function Vvveb\email;
use Vvveb\System\Functions\Str;
use Vvveb\System\Locale;
use Vvveb\System\Sites;
use Vvveb\System\User\Admin;
use function Vvveb\url;

#[\AllowDynamicProperties]
class Reset {
	/**
	 * Process the password reset form submission.
	 *
	 * @return void
	 */
	public function reset(): void {
		$token           = $this->request->get['token'] ?? false;
		$user            = $this->request->get['user'] ?? false;
		$password        = $this->request->post['password'] ?? false;
		$confirmPassword = $this->request->post['confirm_password'] ?? false;
		$admin           = [];

		if ($user && $token) {
			$admin = Admin::get(['username' => $user, 'token' => $token, 'status' => 1]);

			if ($password) {
				if ($confirmPassword && ($password === $confirmPassword)) {
					if ($admin) {
						if (Admin::update(['token' => '', 'password' => $password], ['username' => $user, 'token' => $token])) {
							$success                      = __('Password was reset!');
							$this->view->success['login'] = $success;
							$this->session->set('success', ['login' => $success]);
							header('Location: ' . url(['module' => 'user/login']));
							exit();
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

		if (!$admin) {
			$this->view->errors['login'] = __('Invalid or expired token!');
			$this->session->set('errors', ['login' => __('Invalid or expired token!')]);
			header('Location: ' . url(['module' => 'user/login']));
			exit();
		}
	}

	/**
	 * Handle the password reset request page and send reset email.
	 *
	 * @return void
	 */
	public function index(): void {
		$email      = $this->request->post['email'] ?? false;
		$loginData  = [];

		$language = $this->session->get('language') ?? 'en_US';
		Locale::setLanguage($language);

		if ($email) {
			$loginData['email'] = $email;

			if (($adminData = Admin::get($loginData)) != false) {
				//set reset token and send reset email
				$token = Str::random(32);
				Admin::update(['token' => $token], ['email' => $adminData['email']]);

				$agent = $_SERVER['HTTP_USER_AGENT'] ?? '';

				if (str_contains($agent, 'Linux')) {
					$os = 'Linux';
				} elseif (str_contains($agent, '/Win/')) {
					$os = 'Windows';
				} elseif (str_contains($agent, '/Mac/')) {
					$os = 'Mac';
				} else {
					$os = 'UnKnown';
				}

				$site = Sites::getSiteData($this->global['site_id'] ?? SITE_ID);

				$reset_url = url(['module' => 'user/reset', 'action' => 'reset', 'token' => $token, 'user' => $adminData['username'], 'host' => $site['host'] ?? false]);

				$data = $adminData + ['token' => $token, 'operating_system' => $os, 'browser_name' => $_SERVER['HTTP_USER_AGENT'], 'reset_url' => $reset_url];

				\Vvveb\System\Notifications::trigger('admin.password_reset', $data + [
					'subject' => __('Password reset'),
					'message' => __('A password reset was requested for your account.'),
				]);

				if (email($adminData['email'], __('Password reset'), 'user/reset', $data)) {
					$success               = __('A reset email was sent, please use it to reset your password!');
					$this->view->success[] = $success;
					$this->session->set('success', $success);
					header('Location: ' . url(['module' => 'user/login']));
					exit();
				} else {
					$this->view->errors['login'] = __('Error sending reset email!');
				}
			} else {
				$this->view->errors['login'] = __('Email not found!');
			}
		}
	}
}
