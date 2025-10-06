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

use Vvveb\Controller\User\User as UserBase;
use Vvveb\Sql\SiteSQL;

class User extends UserBase {
	protected $type = 'admin';

	function index() {
		parent :: index();

		$roles    = new \Vvveb\Sql\RoleSQL();

		$options    =  [
			'type' => 'admin', //$this->type,
			'limit'=> 100,
		] + $this->global;

		$roles             = $roles->getAll($options);
		$sites             = new SiteSQL();

		$this->view->admin_auth_token_url = \Vvveb\url(['module' => 'admin/auth-token', 'admin_id' => $this->request->get['admin_id'] ?? '']);
		$this->view->sitesList = $sites->getAll()['site'] ?? [];
		$this->view->roles     = $roles ? $roles['role'] : [];
	}
}
