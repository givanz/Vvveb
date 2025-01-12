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
use Vvveb\System\Cache;
use Vvveb\System\User\Role as RoleList;

class Role extends Base {
	protected $type = 'role';

	protected $app = 'admin';

	protected $apps = [
		'admin'   => ['permissions' => ['controllers']],
		'rest'    => ['permissions' => ['routes', 'controllers']],
		'graphql' => ['permissions' => ['controllers', 'models']],
	];

	function init() {
		$this->app = $this->request->get['app'] ?? 'admin';

		if (! isset($this->apps[$this->app])) {
			//$this->view->errors[] = __('Invalid app!');
			return $this->notFound(__('Invalid app!'));
		}

		parent::init();
	}

	function index() {
		$role_id = $this->request->get['role_id'] ?? false;

		$cache = Cache::getInstance();
		$tree  = $cache->cache(APP, $this->app . '-permissions',function () {
			$tree = [];
			//$this->view->tree = RoleList::controllers($this->app);
			foreach ($this->apps[$this->app]['permissions'] as $method) {
				$data = RoleList::$method($this->app);

				if ($data) {
					$tree += RoleList::$method($this->app);
				}
				////$this->view->tree = $tree['controller'];
			}

			return $tree;
		}, 259200);

		$this->view->tree = $tree;

		//\Vvveb\dd($this->view->tree);

		//$this->view->controllers  = RoleList::getControllerList($this->app);
		$this->view->capabilities = RoleList::getCapabilitiesList($this->app);
		$this->view->apps         = $this->apps;
		$this->view->app          = $this->app;

		$role             = new RoleSQL();
		$this->view->role = $role->get(['role_id' => $role_id]);

		if ($this->view->role) {
			$permissions = json_decode($this->view->role['permissions'], true);

			if (isset($permissions[$this->app])) {
				$permissions = $permissions[$this->app];
			} else {
				//backward compatibility for admin app
				if ($this->app !== 'admin') {
					$permissions = [];
				}
			}

			$this->view->role['permissions']                 = $permissions;
			$this->view->role['permissions']['deny']         = $this->view->role['permissions']['deny'] ?? [];
			$this->view->role['permissions']['allow']        = $this->view->role['permissions']['allow'] ?? [];
			$this->view->role['permissions']['capabilities'] = $this->view->role['permissions']['capabilities'] ?? [];
		}
	}

	function save() {
		$data         = $this->request->post['role'] ?? [];
		$allow        = $this->request->post['allow'] ?? [];
		$deny         = $this->request->post['deny'] ?? [];
		$capabilities = $this->request->post['capabilities'] ?? [];
		$permissions  = [$this->app => ['deny' => $deny, 'allow' => $allow, 'capabilities' => $capabilities]];

		$role_id = $this->request->get['role_id'] ?? false;

		$role               = new RoleSQL();
		$this->view->role   = $role->get(['role_id' => $role_id]);
		$currentPermissions = json_decode($this->view->role['permissions'], true);

		if ($currentPermissions) {
			//backward compatibility
			if (isset($currentPermissions['allow'])) {
				$currentPermissions['admin']  = $currentPermissions;
				unset($currentPermissions['allow'], $currentPermissions['deny'], $currentPermissions['capabilities']);
			}

			$currentPermissions[$this->app] = $permissions[$this->app];
			$permissions                    = $currentPermissions;
		}

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
