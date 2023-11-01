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
use Vvveb\Sql\UserSQL;
use Vvveb\System\User\User;

class Profile extends Base {
	function index() {
		$userModel = new UserSQL();
		$user      = [];

		if (isset($this->request->post['user'])) {
			$user            = $this->request->post['user'];
			$user['user_id'] = $this->global['user_id'];
			unset($user['status'], $user['user'], $user['token'], $user['created_at']);

			$result = User::update($user, ['user_id' => $this->global['user_id']]);

			if (! $result) {
				$this->view->errors = [$userModel->error];
			} else {
				$message               =  __('Profile saved!');
				$this->view->success[] = $message;
			}
		}

		$user = User::get(['user_id' => $this->global['user_id']]);
		unset($user['password']);

		$this->view->user 	  = $user;
	}
}
