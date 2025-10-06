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

namespace Vvveb\Controller\Admin;

use function Vvveb\__;
use Vvveb\Controller\Listing;
use function Vvveb\humanReadable;
use Vvveb\Sql\Admin_Auth_TokenSQL;
use function Vvveb\url;
///use Vvveb\System\Traits\Crud as CrudTrait;

class AuthToken extends Listing {
	/*
	use CrudTrait {
		CrudTrait::delete as get;
	}*/

	protected $type = 'admin_auth_token';

	protected $list = 'admin_auth_token';

	protected $listController = 'auth_token';

	protected $module = 'admin';

	function index() {
		$this->options['admin_id'] = $this->request->get['admin_id'] ?? false;
		$this->options['limit']    =1000;

		if (! $this->options['admin_id']) {
			return $this->notFound(true, __('User not found!'));
		}

		$this->view->user_url = url(['module' => 'admin/user', 'admin_id' => $this->options['admin_id']]);
		parent::index();
	}

	function save() {
		$admin_auth_token    = $this->request->post['admin_auth_token'] ?? [];
		$admin_id            = $this->request->get['admin_id'] ?? false;

		if ($admin_auth_token && $admin_id) {
			foreach ($admin_auth_token as $key => $token) {
				if ($key == '#') {
					unset($admin_auth_token[$key]);
				}

				if (! $token['admin_auth_token_id']) {
					unset($admin_auth_token[$key]['admin_auth_token_id']);
				}
			}

			$adminAuthToken = new Admin_Auth_TokenSQL();

			if (($result = $adminAuthToken->update(['admin_auth_token' => $admin_auth_token, 'admin_id' => $admin_id] + $this->global))/* && $result['admin_auth_token']*/) {
				$this->view->success['get'] =humanReadable(__($this->type)) . __(' saved!');
			} else {
				$this->view->errors[] = __('Error saving!');
			}
		}

		$this->index();
	}
}
