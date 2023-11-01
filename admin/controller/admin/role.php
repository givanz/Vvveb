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
use Vvveb\Controller\Base;
use Vvveb\Sql\RoleSQL;
use Vvveb\System\User\Role as RoleList;

class Role extends Base {
	protected $type = 'role';

	function index() {
		$role_id = $this->request->get['role_id'] ?? false;

		$tree = [];
		RoleList::mkmap(DIR_APP . 'controller', $tree);
		$this->view->tree = $tree['controller'];

		$controllers             = RoleList::getControllerList();
		$this->view->controllers = $controllers;

		$role             = new RoleSQL();
		$this->view->role = $role->get(['role_id' => $role_id]);

		if ($this->view->role) {
			$this->view->role['permissions']          = json_decode($this->view->role['permissions'], true);
			$this->view->role['permissions']['deny']  = $this->view->role['permissions']['deny'] ? $this->view->role['permissions']['deny'] : [];
			$this->view->role['permissions']['allow'] = $this->view->role['permissions']['allow'] ? $this->view->role['permissions']['allow'] : [];
		}
	}

	function save() {
		$data        = $this->request->post['role'] ?? [];
		$allow       = $this->request->post['allow'] ?? [];
		$deny        = $this->request->post['deny'] ?? [];
		$permissions = ['deny' => $allow, 'allow' => $deny];

		$role_id = $this->request->get['role_id'] ?? false;

		$role = new RoleSQL();

		if ($role_id) {
			$result = $role->edit(['role_id' => $role_id, 'role' => $data + ['permissions' => json_encode($permissions)]]);
		} else {
			$result = $role->add(['role' => $data + ['permissions' => json_encode($permissions)]]);
		}

		if ($result && isset($result['role'])) {
			$this->view->success[] = __('Saved!');
		} else {
			$this->view->errors[] = __('Error saving!');
		}

		return $this->index();
	}
}
