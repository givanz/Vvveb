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
	/**
	 * Cached controller list data.
	 *
	 * @var array<string, mixed>|null
	 */
	private static ?array $data = null;

	/**
	 * Check if a permission (or array of permissions) is allowed by the allow/deny rules.
	 *
	 * @param string|array<int, string> $permission Permission string or array of permission strings.
	 * @param array<int, string>        $allowRules Array of allow rules (supports wildcards).
	 * @param array<int, string>        $denyRules  Array of deny rules (supports wildcards).
	 * @param int                       $role_id    The role ID (unused in logic, reserved for context).
	 *
	 * @return array<string, bool>|bool Associative array<array-key, mixed> of permission=>bool when $permission is an array, or a single bool.
	 */
	public static function has(string|array $permission, array $allowRules = [], array $denyRules = [], int $role_id = 0): array|bool {
		//allow
		$allowRegex = '@^' . (str_replace('*', '.+?', implode('$|^', $allowRules))) . '$@mi';
		//deny
		$denyRegex = '@^' . (str_replace('*', '.+?', implode('$|^', $denyRules))) . '$@mi';

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
			$allowList = array_map(fn ($value) => true, array_flip($matches[0]));
		}

		$denyList = [];

		if (preg_match_all($denyRegex, $list, $matches)) {
			//$denyList = array_map(fn ($value) => false, array_flip($matches[0]));
			$denyList = array_map(fn ($value) => false, array_flip($matches[0]));
		}

		//rules that match exactly the permission takes precedence (does not have *)
		//permission identifiers are generated from controller method names (camelCase)
		//while request-time checks may lowercase the action, so compare
		//case-insensitively but keep the original permission string as the result key
		$permissionKeys = [];

		foreach ($permissions as $value) {
			$permissionKeys[strtolower($value)] = $value;
		}

		$exactAllow = [];

		foreach (array_intersect(array_map('strtolower', $allowRules), array_keys($permissionKeys)) as $match) {
			$exactAllow[$permissionKeys[$match]] = true;
		}

		$exactDeny = [];

		foreach (array_intersect(array_map('strtolower', $denyRules), array_keys($permissionKeys)) as $match) {
			$exactDeny[$permissionKeys[$match]] = false;
		}

		$result = $exactDeny + $exactAllow + $denyList + $allowList;

		if (is_array($permission)) {
			return $result;
		} else {
			return $result[$permission] ?? false;
		}
	}

	/**
	 * Recursively build a permission map from the controller directory tree.
	 *
	 * @param string               $dir   The directory path to scan.
	 * @param array<string, mixed> &$path Reference to the path tree being built.
	 * @param string               $app   The application context (e.g. 'admin').
	 *
	 * @return void
	 */
	public static function mkmap(string $dir, array &$path = [], string $app = 'admin'): void {
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

					if (!isset($path[$name])) {
						$path[$name] = [];
					}

					if (is_dir($dir . '/' . $file)) {
						self::mkmap($dir . '/' . $file, $path[$name], $app);
					} else {
						if (!in_array($filename, ['base', 'crud', 'listing', 'error404', 'error403', 'error500'])) {
							$class = str_replace(DIR_ROOT . $app . DS . 'controller' . DS, '', $dir . DS . $filename);
							$class = str_replace('/', '\\', $class);
							if ($app == APP) {
								$actions = self::getActions($class, $filename, $dir . DS . $file);
							} else {
								$actions = self::getActions2($dir . DS . $file);
							}

							if ($actions) {
								$path[$name][$filename] = $actions;
							}
						}
					}
				}
				closedir($dh);
			}
		}
	}

	/**
	 * Get the controller permission map for an application.
	 *
	 * @param string $app The application context (e.g. 'admin').
	 *
	 * @return array<string, array<string, string>> Associative array<array-key, mixed> of controller => actions.
	 */
	public static function controllers(string $app = 'admin'): array {
		$tree = [];
		self::mkmap(DIR_ROOT . $app . DS . 'controller', $tree, $app);

		return $tree['controller'] ?? [];
	}

	/**
	 * Extract permission actions from a controller class using reflection.
	 *
	 * @param string $file     The fully-qualified controller class name.
	 * @param string $filename The base filename without extension.
	 * @param string $path     The full file path to the controller.
	 *
	 * @return array<string, string> Associative array of method => permission path, or empty array.
	 */
	public static function getActions(string $file, string $filename, string $path): array {
		//echo $file;

		$permission = str_replace('\\', '/', $file);
		$class      = '\Vvveb\Controller\\' . $file;
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
						$data[$method] = "$permission/$method";
						/*
						if ($method == 'index') {
							$data[$method] = $permission;
						} else {
							$data[$method] = "$permission/$method";
						}*/
					}
				}
			}

			return $data;
		}

		return [];
	}

	/**
	 * Extract permission actions from a controller file using regex parsing.
	 *
	 * @param string $file The full file path to the controller.
	 *
	 * @return array<string, string> Associative array<array-key, mixed> of method => permission path.
	 */
	public static function getActions2(string $file): array {
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

		//add index by default
		$data['index'] = $permission;

		if ($methods) {
			foreach ($methods as $method) {
				//ignore constructor
				if ($method[0] != '_') {
					$data[$method] = "$permission/$method";
					/*
					if ($method == 'index') {
						$data[$method] = $permission;
					} else {
						$data[$method] = "$permission/$method";
					}*/
				}
			}
		}

		return $data;
	}

	/**
	 * Get the full controller list with all permissions for an application.
	 *
	 * @param string $app The application context (e.g. 'admin').
	 *
	 * @return array{permissions: array<int, string>} Array with a 'permissions' key containing all permission strings.
	 */
	public static function getControllerList(string $app = 'admin'): array {
		if (self :: $data) {
			return self :: $data;
		}

		$files = [];
		$path  = [DIR_APP . '/controller/*', DIR_PLUGINS . '*/' . $app . '/controller/'];

		while (count($path) > 0) {
			$next = array_shift($path);

			foreach (glob("$next/*") ?: [] as $file) {
				if (is_dir($file)) {
					$path[] = $file;
				}

				if (is_file($file)) {
					if (!in_array(basename($file, '.php'), ['base', 'crud', 'listing', 'error404', 'error403', 'error500'])) {
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

	/**
	 * Get the list of available capabilities for an application.
	 *
	 * @param string $app The application context (e.g. 'admin').
	 *
	 * @return array<string, mixed> Capabilities configuration array<array-key, mixed>.
	 */
	public static function getCapabilitiesList(string $app = 'admin'): array {
		$capabilities       =  include DIR_SYSTEM . 'data' . DS . 'capabilities.php';
		[$capabilities]     = Event :: trigger(__CLASS__, __FUNCTION__, $capabilities);

		return $capabilities;
	}

	/**
	 * Build a route schema tree from the application route configuration.
	 *
	 * @param string $app The application context (e.g. 'app').
	 *
	 * @return array<string, mixed> Nested array<array-key, mixed> representing the route schema tree.
	 */
	public static function routes(string $app = 'app'): array {
		$routes       = \Vvveb\config("$app-routes");
		/** @var array<string, mixed> $routeSchemas string-keyed trie accumulated below */
		$routeSchemas = [];

		foreach ($routes as $route => $options) {
			//rest

			//$path = $options['module'];//str_replace('/rest/', '/', $route);
			$path = str_replace('/rest/', '', $route);

			if (!$path) {
				$path = 'index';
			}

			if (isset($options['schema'])) {
				//$path = $options['schema'];
			}

			if (strpos($path, '/')) {
				$slash = explode('/', $path);

				$current = &$routeSchemas;

				foreach ($slash as $name) {
					if (!isset($current[$name])) {
						$current[$name] = [];
					}
					$current = &$current[$name];
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

	/**
	 * Get the list of database models and their methods from SQL files.
	 *
	 * @param string $app The application context (e.g. 'admin').
	 *
	 * @return array<string, array<string, string>> Associative array<array-key, mixed> of model => method => permission path.
	 */
	public static function models(string $app = 'admin'): array {
		$dirSQL = DIR_ROOT . 'admin' . DS . 'sql' . DS . DB_ENGINE . DS;
		$sqlp   = new Sqlp();
		$models = [];

		$files = glob("$dirSQL*.sql");

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
