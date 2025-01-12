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
use Vvveb\System\Event;
use Vvveb\System\Sites;
use Vvveb\System\Traits\Spam;
use Vvveb\System\User\User;
use Vvveb\System\Validator;
use function Vvveb\url;

class Signup extends \Vvveb\Controller\Base {
	use Spam;

	function addUser() {
		//$this->checkAlreadyLoggedIn();
		$validator = new Validator(['signup']);

		if ($this->request->post &&
			($this->view->errors['login'] = $validator->validate($this->request->post)) === true) {
			$isSpam = $this->isSpam($this->request->post);

			//allow only fields that are in the validator list and remove the rest
			$userInfo                 = $validator->filter($this->request->post);
			$userInfo['display_name'] = $userInfo['first_name'] . ' ' . $userInfo['last_name'];
			$userInfo['username']     = $userInfo['first_name'] . $userInfo['last_name'];
			$userInfo['spam']     	   = $isSpam;

			list($userInfo) = Event :: trigger(__CLASS__, __FUNCTION__ , $userInfo);

			//plugins can also be used to detect spam and set the flag
			if ($userInfo['spam']) {
				$this->view->errors['login'] = __('Spam');

				return;
			}

			if ($userInfo) {
				$result                   = User::add($userInfo);

				$this->view->errors['login'] = [];

				if ($result) {
					if (isset($result['user'])) {
						$message = __('User created!');
						$this->session->set('success',  ['login' => $message]);
						$this->view->success['login'][]     = $message;
						$user_id                            = $result['user'];
						$this->request->request['user_id']  = $user_id;

						$site     = siteSettings();
						$siteData = Sites :: getSiteData();

						$userInfo['website'] = url('user/index', [
							'host'   => $siteData['host'] ?? false,
							'scheme' => $_SERVER['REQUEST_SCHEME'] ?? 'http',
						]);

						try {
							$error =  __('Error sending account creation mail!');

							if (! email([$userInfo['email'], $site['admin-email']], __('Your account has been created!'), 'user/signup', $userInfo)) {
								$this->session->set('errors', ['login' => $error]);
								$this->view->errors[] = $error;
							}
						} catch (\Exception $e) {
							if (DEBUG) {
								$error .= "\n" . $e->getMessage();
							}
							$this->session->set('errors', ['login' => $error]);
							$this->view->errors['login'] = $error;
						}

						return $this->redirect('user/index');
					} else {
						$this->view->errors['login'] = __('This email is already in use. Please use another one.');
					}
				} else {
					$this->view->errors['login'] = __('Error creating account!');
				}
			}
		}
	}

	function index() {
		if ($this->request->post) {
			$this->addUser();
		}
	}
}
