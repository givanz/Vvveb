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
	/** @var string Module name override. */
	protected string $module = '';

	/** @var string Action name override. */
	protected string $action = '';

	/** @var string[]|null List of additional permission strings to check. */
	protected ?array $additionalPermissionCheck = null;

	/**
	 * Permissions map keyed by full "module/action" paths.
	 *
	 * @var array<string, bool>
	 */
	protected array $modulePermissions = [];

	/**
	 * Permissions map keyed by action name only (module prefix stripped).
	 *
	 * @var array<string, bool>
	 */
	protected array $actionPermissions = [];

	/**
	 * Verify that the current admin user has the given permission.
	 *
	 * If the permission is denied, a 403 error page is shown and execution stops.
	 *
	 * @param string|null $permission The permission string (e.g. 'blog/edit'). When null it
	 *                                is derived from the current module/action.
	 *
	 * @return void
	 */
	protected function permission(?string $permission = null): void {
		$module     = strtolower(FrontController::getModuleName());
		$action     = strtolower(FrontController::getActionName());
		$action     = ($action && $action !== 'index') ? '/' . $action : '';
		$permission ??= ($module . $action);

		if (!Admin::hasPermission($permission)) {
			$message              = __('Your role does not have permission to access this action!');
			$this->view->errors[] = $message;

			$adminPath = \Vvveb\adminPath();
			$data      = ['message' => $message];

			if (APP === 'admin') {
				$data['adminPath'] = $adminPath;
			}

			$this->notFound($data, 403, true);

			exit();
		}
	}

	/**
	 * Build permission arrays for the current controller's methods.
	 *
	 * Populates $this->modulePermissions and $this->actionPermissions so
	 * that views can conditionally render UI elements.
	 *
	 * @return void
	 */
	protected function setPermissions(): void {
		$module     = $this->module ?: strtolower(FrontController::getModuleName());
		$action     = $this->action ?: strtolower(FrontController::getActionName());
		$action     = ($action && $action !== 'index') ? '/' . $action : '';

		$methods = get_class_methods($this);
		$methods = array_map(fn (string $value): string => ($value === 'index') ? $module : "$module/$value", $methods);

		if ($this->additionalPermissionCheck !== null) {
			$methods = array_merge($methods, $this->additionalPermissionCheck);
		}

		$permissions = Admin::hasPermission($methods);

		$this->modulePermissions = $permissions;

		$actionPermissions = [];
		foreach ($permissions as $permission => $value) {
			$key                     = str_replace("$module/", '', $permission);
			$actionPermissions[$key] = $value;
		}
		$this->actionPermissions = $actionPermissions;
	}

	/**
	 * Recursively extract permission strings from URL menu entries.
	 *
	 * @param array<int|string, mixed> $array       The menu array to inspect.
	 * @param array<string, string>    $permissions Populated with url => permission mappings.
	 *
	 * @return void
	 */
	protected function getPermissionsFromUrl(array &$array, array &$permissions): void {
		foreach ($array as $k => $v) {
			if (is_array($v)) {
				if (isset($v['url'])) {
					if (isset($v['module'])) {
						$permissions[$v['url']] = $v['module'] . ((isset($v['action']) && $v['action'] !== 'index') ? '/' . $v['action'] : '');
					} else {
						$permissions[$v['url']] = \Vvveb\pregMatch('/module=([^&$]+)/', $v['url'], 1);
					}
				}
				$this->getPermissionsFromUrl($v, $permissions);
			}
		}
	}

	/**
	 * Recursively assign permission values into menu entries based on their URL.
	 *
	 * @param array<int|string, mixed> $array       The menu array to modify.
	 * @param array<string, string>    $permissions url => permission mappings.
	 *
	 * @return void
	 */
	protected function setPermissionsFromUrl(array &$array, array &$permissions): void {
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
