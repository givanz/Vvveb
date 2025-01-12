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
use Vvveb\System\Event;
use Vvveb\System\Sqlp\Sqlp;

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

		//rules that match exactly the permission takes precedence (does not have *)
		$exactAllow = array_intersect($allowRules, $permissions);
		$exactDeny  = array_intersect($denyRules, $permissions);

		if ($exactAllow) {
			$exactAllow = array_map(function ($value) { return true; }, array_flip($exactAllow));
		}

		if ($exactDeny) {
			$exactDeny = array_map(function ($value) { return false; }, array_flip($exactDeny));
		}

		$result = $exactDeny + $exactAllow + $denyList + $allowList;

		if (is_array($permission)) {
			return $result;
		} else {
			return $result[$permission] ?? false;
		}
	}

	public static function mkmap($dir, &$path = [], $app = 'admin') {
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
						if (! in_array($filename, ['base', 'crud', 'listing', 'error404', 'error403', 'error500'])) {
							//$path[$name][$filename] = self::getActions2($dir . '/' . $file);
							$class = str_replace(DIR_ROOT . $app . DS . 'controller' . DS, '', $dir . DS . $filename);
							$class = str_replace('/', '\\', $class);
							//echo $class;
							$actions = self::getActions($class, $filename);

							if ($actions) {
								$path[$name][$filename] = $actions;
							}
						}
					}
				}
				closedir($dh);
			}
		}

		return [];
	}

	public static function controllers($app = 'admin') {
		$tree = [];
		self::mkmap(DIR_ROOT . $app . DS . 'controller', $tree);

		return $tree['controller'] ?? [];
	}

	public static function getActions($file) {
		//echo $file;

		$permission = str_replace('\\', '/', $file);
		$class      = '\\Vvveb\\Controller\\' . $file;
		//if plugin add namespace
		if (strpos($file, 'plugins/')) {
			$pluginName = pregMatch('@/plugins/(.+?)/@', $file, 1);
			$permission = "plugins/$pluginName$permission";
		}

		if (class_exists($class)) {
			$methods = get_class_methods($class);

			$data = [];
			//add index by default
			$data['index'] = $permission;

			if ($methods) {
				foreach ($methods as $method) {
					//ignore constructor
					if ($method[0] != '_' && $method != 'init' && $method != 'goToHelp') {
						if ($method == 'index') {
							$data[$method] = $permission;
						} else {
							$data[$method] = "$permission/$method";
						}
					}
				}
			}

			return $data;
		}

		return [];
	}

	public static function getControllerList($app = 'admin') {
		if (self :: $data) {
			return self :: $data;
		}

		$files = [];
		$path  = [DIR_APP . '/controller/*', DIR_PLUGINS . '*/' . $app . '/controller/'];

		while (count($path) > 0) {
			$next = array_shift($path);

			foreach (glob("$next/*") as $file) {
				if (is_dir($file)) {
					$path[] = $file;
				}

				if (is_file($file)) {
					if (! in_array(basename($file, '.php'), ['base', 'crud', 'listing', 'error404', 'error403', 'error500'])) {
						$files[] = $file;
					}
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

			//add index by default
			$data['permissions'][] = $permission;

			if ($methods) {
				foreach ($methods as $method) {
					//ignore constructor
					if ($method[0] != '_') {
						if ($method == 'index') {
							$data['permissions'][] = $permission;
						} else {
							$data['permissions'][] = "$permission/$method";
						}
					}
				}
			}
		}

		self :: $data = $data;

		return $data;
	}

	public static function getCapabilitiesList($app = 'admin') {
		$capabilities       =  include DIR_SYSTEM . 'data' . DS . 'capabilities.php';
		list($capabilities) = Event :: trigger(__CLASS__,__FUNCTION__, $capabilities);

		return $capabilities;
	}

	public static function routes($app = 'app') {
		$routes       = \Vvveb\config("$app-routes");
		$routeSchemas = [];

		foreach ($routes as $route => $options) {
			//rest

			//$path = $options['module'];//str_replace('/rest/', '/', $route);
			$path = str_replace('/rest/', '', $route);

			if (! $path) {
				$path = 'index';
			}

			if (isset($options['schema'])) {
				//$path = $options['schema'];
			}

			if (strpos($path, '/')) {
				$slash = explode('/', $path);

				if ($slash) {
					$current = &$routeSchemas;

					foreach ($slash as $name) {
						if (! isset($current[$name])) {
							$current[$name] = [];
						}
						$current = &$current[$name];
					}
				}
			} else {
				$current = &$routeSchemas[$path];
			}

			if (isset($options['methods'])) {
				foreach ($options['methods'] as $method) {
					$current[$method] = $path . '/' . $method;
				}
			} else {
				//$routeSchemas[$options['module']] = $options['module'];
			}
		}

		return $routeSchemas;
	}

	public static function models($app = 'admin') {
		$dirSQL = DIR_ROOT . 'admin' . DS . 'sql' . DS . DB_ENGINE . DS;
		$sqlp   = new Sqlp();
		$models = [];

		$files = glob("$dirSQL*.sql");
		//var_dump($files);

		foreach ($files as $file) {
			$sqlp->parseSqlPfile($file);
			$tree  = $sqlp->getModel();
			$model = basename($file, '.sql');

			foreach ($tree as $method => $options) {
				$methodName              = $model . '/' . $method;
				$models[$model][$method] = $methodName;
			}
		}

		return $models;
	}
}
