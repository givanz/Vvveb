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
use Vvveb\Sql\User_failed_loginSQL;
use Vvveb\System\Event;
use Vvveb\System\Functions\Str;
use Vvveb\System\User\User;
use Vvveb\System\Validator;

trait LoginTrait {
	/** @var string|null URL to redirect to after successful login. */
	protected ?string $redirectUrl = null;

	/** @var string Date format string for grouping failed login attempts. */
	protected string $failedTimeInterval = 'Y-m-d H:00:00';

	/** @var int Maximum failed login attempts before account lockout. */
	protected int $failedCount = 10;

	/**
	 * Process login form submission: validate credentials, log failures, authenticate.
	 *
	 * @return void
	 */
	public function login(): void {
		if (isset($this->request->post['logout'])) {
			$success             = __('Logout successful!');
			$this->view->success = [$success];

			User::logout();

			return;
		}

		$validator = new Validator(['login']);

		if ($this->request->post) {
			if (($errors = $validator->validate($this->request->post)) === true) {
				$userInfo = $this->request->post;

				$failedLogin   = new User_failed_loginSQL();
				$date          = date($this->failedTimeInterval);
				$lastIp        = $_SERVER['REMOTE_ADDR'] ?? '';
				$failedAttemps = $failedLogin->get(['updated_at' => $date, 'status' => 1] + $userInfo);

				if ($failedAttemps && ($failedAttemps['count'] > $this->failedCount)) {
					$this->view->errors['login'] = __('Too many login attempts, try again in one hour!');
					$failedLogin->logFailed(['last_ip' => $lastIp, 'updated_at' => $date] + $userInfo);

					return;
				}

				[$userInfo] = Event::trigger(__CLASS__, __FUNCTION__, $userInfo);

				if (!$userInfo) {
					//cancelled by event listener
					return;
				}

				if ($user = User::login($userInfo)) {
					$success = __('Login successful!');
					$this->session->set('success', ['login' => $success]);
					$this->session->close();
					$this->view->success['login']         = $success;
					$this->view->global['user_id']        = $user['user_id'];
					$this->redirect($this->redirectUrl ?? 'user/index');
				} else {
					//user not found or wrong password
					$this->view->errors['login'] = __('Authentication failed, wrong email or password!');
					$this->session->set('csrf', Str::random());
					//increment failed attempts
					$failedLogin->logFailed(['last_ip' => $lastIp, 'updated_at' => $date] + $userInfo);
				}
			} else {
				if ($errors != true) {
					$this->view->errors['login'] = $errors;
				}
			}
		}
	}
}
