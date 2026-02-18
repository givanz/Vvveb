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
use function Vvveb\model;
use function Vvveb\sanitizeHTML;
use Vvveb\System\Core\View;
use Vvveb\System\Images;
use Vvveb\System\Sites;
use Vvveb\System\User\Admin;
use Vvveb\System\User\User as UserSystem;
use Vvveb\System\Validator;

class User extends Base {
	protected $type = 'user';

	function index() {
		$view = View :: getInstance();

		$user_id = (int)($this->request->get[$this->type . '_id'] ?? false);


		if ($user_id) {
			$options = [$this->type . '_id' => (int)$user_id];

			if ($this->type == 'admin') {
				if (Admin::hasCapability('view_other_admin')) {
				} else {
					if ($user_id != $this->global['admin_id']) {
						$message = __('Permission denied!');
						$view->errors[] = $message;
						$this->notFound($message, 403, true);
					}
				}
			}

			$users      = model($this->type);
			$user       = $users->get($options);

			if (! $user) {
				return $this->notFound(__('User not found!'));
			}

			if (isset($user['password'])) {
				//don't show password hash
				unset($user['password']);
			}

			//featured avatar
			foreach (['avatar', 'cover'] as $image) {
				if (isset($user[$image])) {
					$user[$image . '_url']= Images::image($user[$image], $this->type);
				}
			}

			if (isset($user['site_access'])) {
				$user['site_access'] = json_decode($user['site_access']);
			}

			$view->user = $user;
		}

		$view->type      = $this->type;
		$admin_path      = \Vvveb\adminPath();
		$controllerPath  = $admin_path . 'index.php?module=media/media';
		$view->scanUrl   = "$controllerPath&action=scan";
		$view->uploadUrl = "$controllerPath&action=upload";
		$view->themeCss  = '';

		$theme = Sites::getTheme($this->global['site_id']) ?? 'default';
		if (file_exists(DIR_THEMES . "$theme/css/admin-post-editor.css")) {
			$view->themeCss .= PUBLIC_PATH . "themes/$theme/css/admin-post-editor.css";
		} else {
			if (file_exists(DIR_THEMES . "$theme/css/style.css")) {
				$view->themeCss .= PUBLIC_PATH . "themes/$theme/css/style.css";
			}
		}

		foreach (['custom.css', 'fonts.css'] as $css) {
			if (file_exists(DIR_THEMES . "$theme/css/$css")) {
				$view->themeCss .= ',' . PUBLIC_PATH . "themes/$theme/css/$css";
			}
		}

		if ($this->type == 'user') {
			$userGroup           = model('user_group');
			$groups              = $userGroup->getAll(['status' => 1] + $this->global)['user_group'] ?? '';
			$view->user_group_id = $groups;
		}
	}

	function loginAs() {
		$user_id = (int)($this->request->get[$this->type . '_id'] ?? false);

		if ($user_id) {
			$view      = View :: getInstance();

			$userInfo = UserSystem::get(['user_id' => $user_id]);

			if ($userInfo) {
				\Vvveb\session(['user' => $userInfo]);
				$this->view->global['user_id'] = $userInfo['user_id'];
				$success                       = __('Login successful!');
				$success .= '<a class="btn btn-outline-success btn-sm ms-2" target="_blank" href="/user">' . __('View website') . '</a>';
				$view->success[]               = $success;
			} else {
				$error          = __('Login failed!');
				$view->errors[] = $error;
			}
		}

		return $this->index();
	}

	function save() {
		$validator = new Validator([$this->type]);
		$view      = View :: getInstance();

		$user_id = (int)($this->request->get[$this->type . '_id'] ?? false);
		$user    = $this->request->post[$this->type] ?? [];

		if ($this->type == 'admin') {
			$editCapability = 'edit_other_admin';
			if (Admin::hasCapability($editCapability)) {
			} else {
				if (! $user_id || $user_id != $this->global['admin_id']) {
					$view->errors[] = __('Permission denied!');
					return;
				}
			}
		}


		if (($errors = $validator->validate($user)) === true) {
			$users = model($this->type);
			$user  = $this->request->post[$this->type] ?? [];

			//if no password provided don't change
			if (empty($user['password'])) {
				unset($user['password']);
			} else {
			}

			if (isset($user['site_access'])) {
				if (is_array($user['site_access'])) {
					$user['site_access'] = json_encode($user['site_access']);
				} else {
					$user['site_access'] = '[]';
				}
			} else {
				$user['site_access'] = '[]';
			}

			$user['updated_at'] = date('Y-m-d H:i:s');
			$user['bio'] = sanitizeHTML($user['bio']);

			if ($user_id) {
				if ($this->type == 'admin') {
					$result = Admin::update($user, [$this->type . '_id' => $user_id]);
				} else {
					$result = UserSystem::update($user, [$this->type . '_id' => $user_id]);
				}

				if ($result >= 0) {
					$this->view->success[] = ucfirst($this->type) . __(' saved!');
				} else {
					$this->view->errors[] = $users->error;
				}
			} else {
				if ($this->type == 'admin') {
					$result = Admin::add($user);
				} else {
					$result = UserSystem::add($user);
				}

				//$result = $users->add([$this->type => $user]);

				if ($result) {
					if (isset($result[$this->type])) {
						$id = $result[$this->type];

						if (! $id) {
							$view->errors[] = $users->error;
						} else {
							$view->success['get'] = ucfirst($this->type) . __(' added!');
							$this->redirect(['module'=> $this->type . '/user', $this->type . '_id' => $id, 'success' => ucfirst(__($this->type)) . __(' added!')]);
						}
					} else {
						if ($result['email'] == $user['email']) {
							$view->errors[] = __('This email is already in use. Please use another one.');
						}

						if ($result['username'] == $user['username']) {
							$view->errors[] = __('This username is already in use. Please use another one.');
						}
					}
				} else {
					$view->errors[] = __('Error creating account!');
				}
			}
		} else {
			$view->errors = $errors;
		}

		$this->index();
	}
}
