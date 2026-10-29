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
use Vvveb\Sql\Admin_auth_tokenSQL;
use Vvveb\System\User\Admin;
use function Vvveb\url;

class AuthToken extends Listing {
	/**
	 * Auth token type identifier.
	 *
	 * @var string
	 */
	protected string $type = 'admin_auth_token';

	/**
	 * List key used to identify the data collection.
	 *
	 * @var string
	 */
	protected string $list = 'admin_auth_token';

	/**
	 * Plural controller name for listing views.
	 *
	 * @var string
	 */
	protected string $listController = 'auth_token';

	/**
	 * Module path this controller belongs to.
	 *
	 * @var string
	 */
	protected string $module = 'admin';

	/**
	 * Display the auth token list for a specific admin user.
	 *
	 * @return void
	 */
	public function index(): void {
		$this->options['admin_id'] = $this->request->get['admin_id'] ?? false;
		$this->options['limit']    = 1000;

		if (!$this->options['admin_id']) {
			$this->notFound(__('User not found!'));

			return;
		}

		if (!Admin::hasCapability('view_other_admin')) {
			if ($this->options['admin_id'] != $this->global['admin_id']) {
				$message              = __('Permission denied!');
				$this->view->errors[] = $message;
				$this->notFound($message, 403, true);
			}
		}

		$this->view->user_url = url(['module' => 'admin/user', 'admin_id' => $this->options['admin_id']]);
		parent::index();
	}

	/**
	 * Save auth token data for a specific admin user.
	 *
	 * @return void
	 */
	public function save(): void {
		$admin_auth_token    = $this->request->post['admin_auth_token'] ?? [];
		$admin_id            = $this->request->get['admin_id'] ?? false;

		$editCapability = 'edit_other_admin';
		if (!Admin::hasCapability($editCapability)) {
			if (!$admin_id || $admin_id != $this->global['admin_id']) {
				$this->view->errors[] = __('Permission denied!');

				return;
			}
		}

		if ($admin_auth_token && $admin_id) {
			foreach ($admin_auth_token as $key => $token) {
				if ($key == '#' || $key == '#admin_auth_token') {
					unset($admin_auth_token[$key]);
				}

				if (!$token['admin_auth_token_id']) {
					unset($admin_auth_token[$key]['admin_auth_token_id']);
				}
			}

			$adminAuthToken = new Admin_Auth_TokenSQL();

			if (($result = $adminAuthToken->update(['admin_auth_token' => $admin_auth_token, 'admin_id' => $admin_id] + $this->global))/* && $result['admin_auth_token']*/) {
				$this->view->success['get'] = humanReadable(__($this->type)) . __(' saved!');
			} else {
				$this->view->errors[] = __('Error saving!');
			}
		}

		$this->index();
	}
}
