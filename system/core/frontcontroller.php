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

namespace Vvveb\System\Core;

use function Vvveb\__;
use Vvveb\System\Event;
use Vvveb\System\PageCache;
use Vvveb\System\Routes;
use Vvveb\System\Session;

#[\AllowDynamicProperties]
class FrontController {
	/**
	 * Current module (controller) name.
	 *
	 * @var string
	 */
	private static string $moduleName = '';

	/**
	 * Current action name.
	 *
	 * @var string
	 */
	private static string $actionName = '';

	/**
	 * Current HTTP status code.
	 *
	 * @var int
	 */
	private static int $status = 200;

	/**
	 * Returns current controller name.
	 *
	 * @return string
	 */
	public static function getModuleName(): string {
		return self::$moduleName;
	}

	/**
	 * Returns current action name.
	 *
	 * @return string
	 */
	public static function getActionName(): string {
		return self::$actionName;
	}

	/**
	 * Returns current HTTP status code.
	 *
	 * @return int
	 */
	public static function getStatus(): int {
		return self::$status;
	}

	/**
	 * Sets the current HTTP status code.
	 *
	 * @param int $status
	 *
	 * @return int
	 */
	public static function setStatus(int $status): int {
		return self::$status = $status;
	}

	/**
	 * Handles 404 (or other error) responses.
	 *
	 * @param mixed                                $service
	 * @param string|array<array-key, mixed>|false $message
	 * @param int                                  $statusCode
	 *
	 * @return never
	 */
	public static function notFound(mixed $service = false, string|array|false $message = false, int $statusCode = 404): never {
		self::$status = $statusCode;

		$file = DIR_APP . DS . 'controller' . DS . "error$statusCode.php";
		if (file_exists($file)) {
			$controller       = 'Vvveb\Controller\Error' . $statusCode;
			self::$moduleName = 'error' . $statusCode;
			$view             = View::getInstance();
			if (is_array($message)) {
				$view->set($message);
			} else {
				$view->message = $message;
			}

			$view->file  = '';
			$view->trace = [];

			self::call($controller, 'index', $file);
			PageCache::getInstance()->cleanUp();
			exit();
		} else {
			PageCache::getInstance()->cleanUp();

			http_response_code($statusCode);
			exit("Http error $statusCode");
		}
	}

	/**
	 * Extension point run after the response is sent.
	 *
	 * Currently a no-op: connection teardown is left to PHP shutdown.
	 *
	 * @return void
	 */
	public static function closeConnections(): void {}

	/**
	 * Inject dependencies into a controller instance.
	 *
	 * @param object &$controller
	 *
	 * @return void
	 */
	public static function di(object &$controller): void {
		$controller->request  = Request::getInstance();
		$controller->response = Response::getInstance();
		$controller->view     = View::getInstance();
		$controller->session  = Session::getInstance();
	}

	/**
	 * Builds the structured error payload used by dispatch failures.
	 *
	 * @param string                      $message Human readable error message.
	 * @param string|array<string, mixed> $detail  Extra context included with the error.
	 *
	 * @return array<string, mixed>
	 */
	private static function errorPayload(string $message, string|array $detail = []): array {
		$payload = ['message' => $message];

		if (is_array($detail)) {
			$payload += $detail;
		}

		return $payload;
	}

	/**
	 * Route to the 404 handler for a missing controller method.
	 *
	 * @param string $controllerClass
	 * @param string $actionName
	 *
	 * @return never
	 */
	private static function methodNotFound(string $controllerClass, string $actionName): never {
		self::notFound(false, self :: errorPayload(
			__('Method does not exist!'),
			['file' => "$controllerClass :: $actionName"]
		));
	}

	/**
	 * Initializes the controller class and calls the action.
	 *
	 * @param string       $controllerClass
	 * @param string       $actionName
	 * @param string|false $file
	 *
	 * @return mixed
	 */
	public static function call(string $controllerClass, string $actionName, string|false $file = false): mixed {
		if ((!@include_once (DIR_APP . DS . 'controller' . DS . 'base.php')) ||
			 (!file_exists($file) || !@include_once ($file))) {
			return self::notFound(false, self :: errorPayload(__('Controller file not found!'), ['file' => $file]));
		}

		// We check if the controller's class really exists
		$controller = false;

		if (class_exists($controllerClass, false)) {// if the controller does not exist route to controller main
			$controller = new $controllerClass();

			if (!method_exists($controller, $actionName) ||
			$actionName === 'init' || str_starts_with($actionName, '_')) {
				return self::methodNotFound($controllerClass, $actionName);
			}
		} else {
			return self::notFound(false, self :: errorPayload(
				__('Controller does not exist!'),
				$controllerClass
			));
		}

		self::di($controller);

		if (method_exists($controller, 'init')) {
			$return = $controller->init();

			if ($return) {
				$actionName = $return;

				if (!method_exists($controller, $actionName) || str_starts_with($actionName, '_')) {
					return self::methodNotFound($controllerClass, $actionName);
				}
			}
		}

		$response = Response::getInstance();
		$template = str_replace('/', DS, strtolower(self::$moduleName));
		$theme 	  = $controller->view->getTheme();
		$path     = DIR_THEME . $theme . DS;
		$isPlugin = str_starts_with($template, 'plugins/');

		if ($actionName && $actionName !== 'index') {
			if ($isPlugin) {
				$t          = str_replace('plugins' . DS, '', $template);
				$p          = str_contains($t, DS) ? strpos($t, DS) : false;
				$pluginName = substr($t, 0, $p);
				$nameSpace  = substr($t, $p + 1);

				$html = DIR_PLUGINS . $pluginName . DS . 'public' . DS . APP . DS . $nameSpace . DS . strtolower($actionName) . '.html';
			} else {
				$html = $path . $template . DS . strtolower($actionName) . '.html';
			}

			if (is_file($html)) {
				$template .= DS . strtolower($actionName);
			}
		}

		$controller->view->template($template . '.html'); //default html that can be overwritten

		$template     = call_user_func([$controller, $actionName]);

		[$template, $controller, $actionName] = Event::trigger(__CLASS__, __FUNCTION__, $template, $controller, $actionName);

		$responseType = $response->getType();
		$controller->view->setType($responseType);

		if ($template === false) {
			$controller->view->template(false);
		} else {
			if (is_array($template)) {
				$response->output($template);
			} else {
				if ($template) {
					$controller->view->template($template);
				}
			}
		}

		$response->setStatus(self::$status);
		$return = $response->output();
		self::closeConnections();

		return $return;
	}

	/**
	 * Redirect or direct to a action or default controller action and parameters.
	 * It has the ability to http redirect to the specified action.
	 * Internally used to direct to action.
	 *
	 * @param string $moduleName
	 * @param string $actionName
	 *
	 * @return mixed
	 */
	public static function redirect(string $moduleName, string $actionName = 'index'): mixed {
		self::$moduleName = $moduleName;
		self::$actionName = $actionName;

		if (is_dir(DIR_APP . DS . 'controller' . DS . strtolower($moduleName))) {
			self::$moduleName = $moduleName .= '/Index';
		}

		$dir = strtolower(str_replace('/', DS, $moduleName));

		$className       = \Vvveb\dashesToCamelCase(str_replace(['/', DS], '\\', $moduleName));
		$controllerClass = 'Vvveb\Controller\\' . $className;

		//change file paths for plugins
		if (str_starts_with($moduleName, 'Plugins/')) {
			$dir             = str_replace('plugins' . DS, '', $dir);
			$p               = strpos($dir, DS);
			$pluginName      = $dir;
			$nameSpace       = 'index';

			if ($p !== false) {
				$pluginName      = substr($dir, 0, $p);
				$nameSpace       = substr($dir, $p + 1);
			}
			//$className       = str_replace('Plugins\\', '', $className);
			$file            = DIR_PLUGINS . $pluginName . DS . APP .
							   DS . 'controller' . DS . "$nameSpace.php";
			$pluginName      = \Vvveb\dashesToCamelCase($pluginName);
			//insert Controller namespace
			$className  	    = str_replace('Plugins\\' . $pluginName, $pluginName . '\Controller', $className);
			$controllerClass = 'Vvveb\Plugins\\' . $className;
		} else {
			$file = DIR_APP . 'controller' . DS . $dir . '.php';
		}

		return self::call($controllerClass, $actionName, $file);
	}

	/**
	 * Returns the current route from the query string.
	 *
	 * @return string
	 */
	public static function getRoute(): string {
		return $_GET['route'] ?? '';
	}

	/**
	 * Returns the current module from the query string.
	 *
	 * @return string
	 */
	public static function getModule(): string {
		return $_GET['module'] ?? '';
	}

	/**
	 * Dispatches the request to the appropriate controller/action.
	 *
	 * @param array<array-key, mixed> &$site
	 *
	 * @return mixed
	 */
	public static function dispatch(array &$site = []): mixed {
		$module   = $_GET['module'] ?? $_POST['module'] ?? null;
		$action   = $_GET['action'] ?? $_POST['action'] ?? null;
		$_REQUEST = array_merge($_GET, $_REQUEST);

		//remove GET parameters to allow correct matching,
		$uri   = SITE_URI;
		$uri   = preg_replace('/\?.*$/', '', $uri);

		$route        = false;
		$routesSiteId = null;
		if (isset($site['route'])) {
			$routesSiteId = $site['site_id'];
		}

		if (!$module && (APP !== 'admin' && APP !== 'install' && (Routes::init(APP, $routesSiteId) && $route = Routes::match($uri)))) {
			$_GET = array_merge($route, $_GET);
		} else {
			$module         = $module ?? ((APP === 'install' || APP === 'admin') ? 'index' : false);

			if (!$module && APP === 'app') {
				return self::notFound(false, ['message' => 'No route!']);
			}
		}

		if ($route) {
			if (preg_match('@(^.+?)/(\w+$)@', $_GET['module'], $routeMatch)) {
				$module = $routeMatch[1];
				$action ??= $routeMatch[2];
			} else {
				$module = trim($_GET['module'], '/');
			}
		}

		if (empty($action)) {
			$action = 'index';
		}
		//santize inputs
		if ((!preg_match('@^[a-zA-Z_/0-9\-]{4,70}$@', $module)) ||
			(!preg_match('@^[a-zA-Z_/0-9\-]{3,70}$@', $action))) {
			return self::notFound(false, ['message' => 'Invalid request!'], 500);
		}

		//capitalize each namespace segment of the module path (ex: content/posts -> Content/Posts)
		$path = implode('/', array_map('ucfirst', explode('/', $module)));

		return self::redirect($path, $action);
	}
}
