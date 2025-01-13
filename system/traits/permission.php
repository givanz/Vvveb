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

namespace Vvveb\System\Traits;

use function Vvveb\__;
use Vvveb\System\Core\FrontController;
use Vvveb\System\User\Admin;

trait Permission {
	/*
	 * Permission check for each module/action
	 */
	protected function permission($permission = null) {
		$module     = $this->module ?? strtolower(FrontController::getModuleName());
		$action     =  $this->action ?? strtolower(FrontController::getActionName());
		$action     = ($action && $action != 'index') ? '/' . $action : '';
		$permission = $permission ?? ($module . $action);

		//if current module/action does not have permission then show permission denied page
		if (! Admin::hasPermission($permission)) {
			$message              = __('Your role does not have permission to access this action!');
			$this->view->errors[] = $message;

			$adminPath = \Vvveb\adminPath();
			$data      = ['message' => $message];

			if (APP == 'admin') {
				$data['adminPath'] = $adminPath;
			}

			$this->notFound($data, 403);

			die(0);
		}
	}

	protected function setPermissions() {
		$module     = $this->module ?? strtolower(FrontController::getModuleName());
		$action     =  $this->action ?? strtolower(FrontController::getActionName());
		$action     = ($action && $action != 'index') ? '/' . $action : '';

		//get current controller methods to check for permission
		$methods = get_class_methods($this);
		//$methods = array_map(fn ($value) => "$module/$value", $methods);
		$methods = array_map(function ($value) use ($module) {return ($value == 'index') ? $module : "$module/$value"; }, $methods);

		//check if controller requires additional permission check
		if (isset($this->additionalPermissionCheck)) {
			$methods = array_merge($methods, $this->additionalPermissionCheck);
		}

		$permissions = Admin::hasPermission($methods);

		//set a permission array only with action keys for easier permission check in html
		$this->modulePermissions = $permissions;

		foreach ($permissions as $permission => &$value) {
			$key                     = str_replace("$module/", '', $permission);
			$actionPermissions[$key] = $value;
		}
		$this->actionPermissions = $actionPermissions;
	}

	protected function getPermissionsFromUrl(&$array, &$permissions) {
		foreach ($array as $k => $v) {
			if (is_array($v)) {
				if (isset($v['url'])) {
					if (isset($v['module'])) {
						$permissions[$v['url']] = ($v['module'] ?? '') . ((isset($v['action']) && $v['action'] != 'index') ? '/' . $v['action'] : '');
					} else {
						$permissions[$v['url']] = \Vvveb\pregMatch('/module=([^&$]+)/', $v['url'], 1);
					}
				}
				$this->getPermissionsFromUrl($v, $permissions);
			}
		}
	}

	protected function setPermissionsFromUrl(&$array, &$permissions) {
		foreach ($array as $k => &$v) {
			if (is_array($v)) {
				if (isset($v['url'])) {
					$url = $v['url'];

					if (isset($permissions[$url])) {
						$v['permission'] = $permissions[$url];
					}
				}
				$this->setPermissionsFromUrl($v, $permissions);
			}
		}
	}
}
