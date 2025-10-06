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

namespace Vvveb\Controller\Content;

use function Vvveb\__;
use Vvveb\Controller\Base;
use Vvveb\System\User\Admin;

class User extends Base {
	function index() {
		$admin_id    = $this->request->get['admin_id'] ?? '';
		$username    = $this->request->get['username'] ?? '';

		if ($admin_id || $username) {
			$options = ['admin_id' => $admin_id, 'username' => $username];
			$user    = Admin::get($options);

			if ($user) {
				$this->request->get['admin_id'] = $user['admin_id'];
			} else {
				$error = sprintf(__('%s not found!'), ucfirst(__('user')));

				return $this->notFound(true, ['message' => $error, 'title' => $error]);
			}
		}
	}
}
