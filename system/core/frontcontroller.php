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

use Vvveb\System\Component\Component;
use Vvveb\System\PageCache;
use Vvveb\System\Routes;
use Vvveb\System\Session;

#[\AllowDynamicProperties]
class FrontController {
	/**
	 * Standard Controller constructor.
	 */
	static private $moduleName;

	static private $actionName;

	static private $app = 'app';

	static public $rewrite;

	static private $status = 200;

	/**
	 * Returns current controller name.
	 *
	 * @return string
	 */
	static function getModuleName() {
		return self :: $moduleName;
	}

	static function app($app = null) {
		if ($app) {
			self :: $app = $app;
		}

		return self :: $app;
	}

	/**
	 * Returns current controller name.
	 *
	 * @return string
	 */
	static function getActionName() {
		return self :: $actionName;
	}

	/**
	 * Returns current status.
	 *
	 * @return string
	 */
	static function getStatus() {
		return self :: $status;
	}

	static private function callAction($module, $action = 'index') {
		//header(' ', true, $statusCode);
		if (include_once DIR_APP . DS . 'controller' . DS . "$controller.php") {
			$controller         = 'Vvveb\Controller\\' . $controller;
			self :: $moduleName = $moduleName = $controller;
			$controller         = 'Vvveb\Controller\\' . $controller;
		}
	}

	static function notFound($service = false, $message = false, $statusCode = 404) {
		self :: $status = $statusCode;
		//@header(' ', true, $statusCode);
		/*
				$view = View :: getInstance();
				//$view = new View();
				$view->setTheme();

				if (is_string($message)) {
					$message = ['message' => $message];
				}

				$view->set($message);

				PageCache::getInstance()->cleanUp();
				self :: redirect('Error' . $statusCode, 'index');

				die();*/

		if (include_once DIR_APP . DS . 'controller' . DS . "error$statusCode.php") {
			$controller         = 'Vvveb\Controller\Error' . $statusCode;
			self :: $moduleName = $moduleName = 'error' . $statusCode;
			$controller         = 'Vvveb\Controller\Error' . $statusCode;
			//header("HTTP/1.1 $statusCode" /* . substr(str_replace(($message ?? ''), "\n", ' '), 100)*/);
			http_response_code($statusCode);
		} else {
			//header(' ', true, $statusCode);
			http_response_code($statusCode);

			die("Http error $statusCode");
		}

		//$view = View :: getInstance();
		$view = new View();
		$view->setTheme();

		if (is_string($message)) {
			$message = ['message' => $message];
		}

		$view->set($message);
		$view->template(self :: $moduleName . '.html'); //default html

		$controller = new $controller();
		$template   = call_user_func([$controller, 'index']);
		unset($controller);
		/*
		if ($service === true) {
			$component = Component :: getInstance();
		}
		 */
		//header(' ', true, $statusCode);
		$response = Response::getInstance();
		$view->setType($response->getType('json'));

		//return $response->output();
		PageCache::getInstance()->cleanUp();
		$view->render(false, true, $service);

		die();
		//self :: closeConnections();
	}

	static function closeConnections() {
		\Vvveb\System\Db::getInstance()->close();
	}

	/**
	 * Inject dependencies.
	 * 
	 * @param string $controller
	 */
	static function di(&$controller) {
		$controller->request  = Request::getInstance();
		$controller->response = Response::getInstance();
		$controller->view     = View::getInstance();
		$controller->session  = Session::getInstance();
	}

	/**
	 * Initializes the controller class and calls the action.
	 * 
	 * @param string $controllerClass
	 * @param string $actionName
	 * @param string $file
	 */
	static function call($controllerClass, $actionName, $file = false) {
		if ((! @include_once(DIR_APP . DS . 'controller' . DS . 'base.php')) ||
			 (! file_exists($file) || ! @include_once($file))) {
			$message = [
				'message' => 'Controller file not found!',
				'file'    => $file,
			];

			return self :: notFound(false, $message);
		}

		// We check if the controller's class really exists
		if (class_exists($controllerClass , false)) {// if the controller does not exist route to controller main
			$controller = new $controllerClass();

			if (! $controller || ! method_exists($controller , $actionName) || $actionName == 'init') {
				$message = [
					'message' => 'Method does not exist!',
					'file'    => "$controllerClass ::  $actionName",
				];

				return self :: notFound(false, $message);
			}
		} else {
			$message = [
				'message' => 'Controller does not exist!',
				'file'    => $controllerClass,
			];

			return self :: notFound(false, $message);
		}

		self :: di($controller);

		if (method_exists($controller, 'init')) {
			$controller->init();
		}

		//$controller->db = $db;
		$template = str_replace('/', DS, strtolower(self :: $moduleName));
		$theme 	  = $controller->view->getTheme();
		$path     = DIR_THEME . $theme . DS;

		if ($actionName && $actionName != 'index') {
			$html = $path . $template . DS . strtolower($actionName) . '.html';

			if (file_exists($html)) {
				$template .= DS . strtolower($actionName);
			}
		}

		$controller->view->template($template . '.html'); //default html
		$template = call_user_func([$controller, $actionName]);

		if ($template === false) {
			$controller->view->template(false);
		} else {
			if (is_array($template)) {
				echo json_encode($template);
			} else {
				if ($template) {
					$controller->view->template($template); //default html
				}
			}
		}

		$controller->view = View :: getInstance();

		//render template
		//return $controller->view->render();
		$response = Response::getInstance();

		$controller->view->setType($response->getType('json'));

		$return = $response->output();
		self :: closeConnections();

		return $return;
	}

	/**
	 * Redirect or direct to a action or default controller action and parameters
	 * it has the ability to http redirect to the specified action
	 * internally used to direct to action.
	 *
	 * @param string $moduleName
	 * @param string $actionName
	 * @param array $parameters
	 * @param bool $httpRedirect
	 * @return bool
	 */
	static function redirect($moduleName , $actionName = 'index') {
		self :: $moduleName = $moduleName;
		self :: $actionName = $actionName;

		if (is_dir(DIR_APP . DS . 'controller' . DS . strtolower($moduleName))) {
			self :: $moduleName = $moduleName .= '/Index';
		}

		$dir = strtolower(str_replace('/', DS, $moduleName));

		$className       = \Vvveb\dashesToCamelCase(str_replace(['/', DS], '\\',  $moduleName));
		$controllerClass = 'Vvveb\Controller\\' . $className;

		//change file paths for plugins
		if (strpos($moduleName, 'Plugins/') === 0) {
			$dir             = str_replace('plugins' . DS, '', $dir);
			$p               = strpos($dir, DS);
			$pluginName      = substr($dir, 0, $p);
			$nameSpace       = substr($dir, $p + 1);
			$className       = str_replace('Plugins\\', '', $className);
			$file            = DIR_PLUGINS . $pluginName . DS . APP .
							   DS . 'controller' . DS . "$nameSpace.php";
			$pluginName      = \Vvveb\dashesToCamelCase($pluginName);
			//insert Controller namespace
			$className  	    = str_replace($pluginName, $pluginName . '\Controller', $className);
			$controllerClass = 'Vvveb\Plugins\\' . $className;
		} else {
			$file = DIR_APP . 'controller' . DS . $dir . '.php';
		}

		return self :: call($controllerClass, $actionName, $file);
	}

	static public function getRoute() {
		return $_GET['route'] ?? '';
	}

	static public function dispatch() {
		$module = $_GET['module'] ?? $_POST['module'] ?? null;
		$action = $_GET['action'] ?? $_POST['action'] ?? null;

		if (empty($action)) {
			$action = 'index';
		}

		$_REQUEST = array_merge($_GET, $_REQUEST);

		//subdirectory support
		/*
		$subdir = \Vvveb\pregMatch('@(.+)/index.php@',$_SERVER['SCRIPT_NAME'], 1);
		$subdir = str_replace(['/public', '/admin'], '', $subdir);
		define('V_SUBDIR_INSTALL', $subdir);
		*/

		//remove GET parameters to allow correct matching,
		$uri = $_SERVER['REQUEST_URI'] ?? '';

		if (V_SUBDIR_INSTALL) {
			$uri = str_replace(V_SUBDIR_INSTALL, '', $uri);
		}
		$uri = preg_replace('/\?.*$/', '', $uri);

		if (! $module && (APP != 'admin' && (Routes::init() && $parameters = Routes::match($uri)))) {
			$_GET = array_merge($parameters, $_GET);
		} else {
			$module = $module ?? ((APP == 'app') ? 'error404' : 'index');
		}

		if (isset($_GET['route'])) {
			if (preg_match('@(^.+?)/(\w+$)@', $_GET['route'], $routeMatch)) {
				$module = $routeMatch[1];
				$action = $routeMatch[2];
			} else {
				$module = trim($_GET['route'], '/');
			}
		}

		//santize inputs
		if (($module && ! preg_match('@^[a-zA-Z_/0-9\-]{4,70}$@', $module)) ||
			($action && ! preg_match('@^[a-zA-Z_/0-9\-]{3,70}$@', $action))) {
			return self::notFound(false, ['message' => 'Invalid request!'], 500);
		}

		$path = $module;

		$module         = ucfirst($module);
		$path           = '';

		array_map(function ($value) use (&$path) {
			if ($path) {
				$path .= '/';
			}
			$path .= ucfirst($value);
		}, explode('/', $module));

		return self :: redirect($path, $action);
	}
}
