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
use function Vvveb\siteSettings;
use Vvveb\System\User\User;
use Vvveb\System\Validator;

class Signup extends \Vvveb\Controller\Base {
	function index() {
		//$this->checkAlreadyLoggedIn();

		$validator = new Validator(['signup']);

		if ($this->request->post &&
			($this->view->errors['signup'] = $validator->validate($this->request->post)) === true) {
			//allow only fields that are in the validator list and remove the rest
			$userInfo                 = $validator->filter($this->request->post);
			$userInfo['display_name'] = $userInfo['first_name'] . ' ' . $userInfo['last_name'];
			$userInfo['username']     = $userInfo['first_name'] . $userInfo['last_name'];
			$result                   = User::add($userInfo);

			$this->view->errors['signup'] = [];

			if ($result) {
				if (is_array($result)) {
					$message = __('User created!');
					$this->session->set('success',  ['signup' => $message]);
					$this->view->success['signup'][]    = $message;
					$user_id                            = $result['user'];
					$this->request->request['user_id']  = $user_id;

					$site = siteSettings();

					try {
						$error =  __('Error sending account creation mail!');

						if (! email([$userInfo['email'], $site['admin-email']], __('Your account has been created!'), 'user/signup', $userInfo)) {
							$this->session->set('errors', ['signup' => $error]);
							$this->view->errors[] = $error;
						}
					} catch (\Exception $e) {
						if (DEBUG) {
							$error .= "\n" . $e->getMessage();
						}
						$this->session->set('errors', ['signup' => $error]);
						$this->view->errors['signup'] = $error;
					}

					return $this->redirect('user/index');
				//header('Location: /user');
				} else {
					$this->view->errors['signup'] = __('This email is already in use. Please use another one."');
				}
			} else {
				$this->view->errors['signup'] = __('Error creating user!');
			}
		}
	}
}
