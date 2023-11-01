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

namespace Vvveb\System\User;

use function Vvveb\pregMatch;
use function Vvveb\pregMatchAll;

class Role {
	static private $data = null;

	/*
	 * Checks for permission
	 * 
	 * $permission string or array
	 */
	public static function has($permission, $allowRules = [], $denyRules = [], $role_id = 0) {
		//$allowRules = ['*', 'content/posts/*', 'settings'];
		//$denyRules  = ['*/save', '*/delete', 'product/*', 'tools/*', 'users/*', 'theme/*', 'plugin/*'];
		//allow
		$allowRegex = '@^' . (str_replace('*', '.+?', implode('$|^', $allowRules))) . '$@m';
		//deny
		$denyRegex = '@^' . (str_replace('*', '.+?', implode('$|^', $denyRules))) . '$@m';

		$result = [];

		if (is_array($permission)) {
			$permissions = $permission;
		} else {
			$permissions = [$permission];
		}
		/*
		foreach ($permissions as $perm) {
			$hasPermission = false;

			if (preg_match($allowRegex, $perm)) {
				$allow = true;
			} else {
				$allow = false;
			}

			if (preg_match($denyRegex, $perm)) {
				$deny = true;
			} else {
				$deny = false;
			}

			$hasPermission = $allow && ! $deny;
			$result[$perm] = $hasPermission;
		}*/

		$list      = implode("\n", $permissions);
		$allowList = [];

		if (preg_match_all($allowRegex, $list, $matches)) {
			//$allowList = array_map(fn ($value) => true, array_flip($matches[0]));
			$allowList = array_map(function ($value) { return true; }, array_flip($matches[0]));
		}

		$denyList = [];

		if (preg_match_all($denyRegex, $list, $matches)) {
			//$denyList = array_map(fn ($value) => false, array_flip($matches[0]));
			$denyList = array_map(function ($value) {return false; }, array_flip($matches[0]));
		}

		$result = $denyList + $allowList;

		if (is_array($permission)) {
			return $result;
		} else {
			return $result[$permission] ?? false;
		}
	}

	public static function add($permission) {
	}

	public static function getTree() {
		$list = $this->getControllerList();
	}

	public static function mkmap($dir, &$path = []) {
		if ($dir[0] == '.') {
			return;
		}

		if (is_dir($dir)) {
			if ($dh = opendir($dir)) {
				while (($file = readdir($dh)) !== false) {
					if ($file[0] == '.') {
						continue;
					}

					$name     = basename($dir);
					$filename = str_replace('.php', '', $file);

					if (is_dir($dir . '/' . $file)) {
						self::mkmap($dir . '/' . $file, $path[$name]);
					} else {
						$path[$name][$filename] = self::getActions($dir . '/' . $file);
					}
				}
				closedir($dh);
			}
		}

		return;
	}

	public static function getActions($file) {
		$permission = substr($file, strpos($file, '/controller/') + 12);
		//remove extension
		$permission = substr($permission, 0, strrpos($permission, '.'));
		//if plugin add namespace
		if (strpos($file, 'plugins/')) {
			$pluginName = pregMatch('@/plugins/(.+?)/@', $file, 1);
			$permission = "plugins/$pluginName$permission";
		}

		$data = [];
		//$data[$permission] = $permission;

		$controllerCode = file_get_contents($file);
		//get all public methods
		$methods = pregMatchAll('/(?<!private|protected)\s+function.+?(\w+)\(/', $controllerCode, 1);

		if ($methods) {
			foreach ($methods as $method) {
				//ignore constructor
				if ($method[0] != '_') {
					$data[$method] = $permission . "/$method";
				}
			}
		}

		return $data;
	}

	public static function getControllerList() {
		if (self :: $data) {
			return self :: $data;
		}

		$files = [];
		$path  = [DIR_APP . '/controller/*', DIR_PLUGINS . '*/admin/controller/'];

		while (count($path) > 0) {
			$next = array_shift($path);

			foreach (glob("$next/*") as $file) {
				if (is_dir($file)) {
					$path[] = $file;
				}

				if (is_file($file)) {
					$files[] = $file;
				}
			}
		}

		sort($files);

		$data['permissions'] = [];
		$tree                = [];

		foreach ($files as $file) {
			//keep only relative controller path
			$permission = substr($file, strpos($file, '/controller/') + 12);
			//remove extension
			$permission = substr($permission, 0, strrpos($permission, '.'));
			//if plugin add namespace
			if (strpos($file, 'plugins/')) {
				$pluginName = pregMatch('@/plugins/(.+?)/@', $file, 1);
				$permission = "plugins/$pluginName$permission";
			}

			$data['permissions'][] = $permission;

			$controllerCode = file_get_contents($file);
			//get all public methods
			$methods = pregMatchAll('/(?<!private)\s+function.+?(\w+)\(/', $controllerCode, 1);

			if ($methods) {
				foreach ($methods as $method) {
					//ignore constructor
					if ($method[0] != '_') {
						$data['permissions'][] = $permission . "/$method";
					}
				}
			}
		}

		self :: $data = $data;

		return $data;
	}
}
