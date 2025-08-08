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
use Vvveb\System\Component\Component;
use Vvveb\System\Event;
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

	static function setStatus($status) {
		return self :: $status = $status;
	}

	static private function callAction($controller, $action = 'index') {
		if ($statusCode !== 200) {
			header(' ', true, $statusCode);
		}

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

				die(0);*/

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
		$response = Response::getInstance();

		if ($service) {
			$view = View::getInstance();
		} else {
			$view = new View();
		}

		$view->setTheme();

		if (is_string($message)) {
			$message = ['message' => $message];
		}

		$view->set($message);
		$view->template(self :: $moduleName . '.html'); //default html

		$controller           = new $controller();
		$controller->response = $response;
		$controller->view     = $view;
		$template             = call_user_func([$controller, 'index']);

		if ($template === false) {
			$view->template(false);
		} else {
			if (is_array($template)) {
				return $response->output($template);
			//echo json_encode($template);
			} else {
				if ($template) {
					$view->template($template);
				}
			}
		}
		/*
		if ($service === true) {
			$component = Component :: getInstance();
		}
		 */
		//header(' ', true, $statusCode);
		PageCache::getInstance()->cleanUp();

		$view->setType($response->getType());
		$view->render();

		die();
		//return $response->output();
		//self :: closeConnections();
		//$view->render($service, true, $service);
	}

	static function closeConnections() {
		if (defined('DB_ENGINE') && ($instance = \Vvveb\System\Db::getInstance())) {
			$instance->close();
		}
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
				'message' => __('Controller file not found!'),
				'file'    => $file,
			];

			return self :: notFound(false, $message);
		}

		// We check if the controller's class really exists
		$controller = false;

		if (class_exists($controllerClass , false)) {// if the controller does not exist route to controller main
			$controller = new $controllerClass();

			if (! $controller || ! method_exists($controller , $actionName) || $actionName == 'init') {
				$message = [
					'message' => __('Method does not exist!'),
					'file'    => "$controllerClass :: $actionName",
				];

				return self :: notFound(false, $message);
			}
		} else {
			$message = [
				'message' => __('Controller does not exist!'),
				'file'    => $controllerClass,
			];

			return self :: notFound(false, $message);
		}

		self :: di($controller);

		if (method_exists($controller, 'init')) {
			$return = $controller->init();

			if ($return) {
				$actionName = $return;

				if (! method_exists($controller , $actionName)) {
					$message = [
						'message' => __('Method does not exist!'),
						'file'    => "$controllerClass :: $actionName",
					];

					return self :: notFound(false, $message);
				}
			}
		}

		$response   = Response::getInstance();
		$template   = str_replace('/', DS, strtolower(self :: $moduleName));
		$theme 	    = $controller->view->getTheme();
		$path       = DIR_THEME . $theme . DS;
		$pluginName = false;

		if ($actionName && $actionName != 'index') {
			$html = $path . $template . DS . strtolower($actionName) . '.html';

			if (is_file($html)) {
				if ($pluginName) {
					$template .= 'plugins' . DS . $nameSpace . DS . strtolower($actionName);
				} else {
					$template .= DS . strtolower($actionName);
				}
			}

			if ($pluginName) {
				$template .= DS . strtolower($actionName);
			}
		}

		$controller->view->template($template . '.html'); //default html that can be overwritten

		//list($template) = Event :: trigger($controllerClass, "$actionName:before", $template);

		//$controller->view->template($template . '.html'); //default html
		$template     = call_user_func([$controller, $actionName]);
		$responseType = $response->getType();
		$controller->view->setType($responseType);

		if ($template === false) {
			$controller->view->template(false);
		} else {
			if (is_array($template)) {
				$response->output($template);
			//echo json_encode($template);
			} else {
				if ($template) {
					$controller->view->template($template);
				}
			}
		}

		//list($responseType) = Event :: trigger($controllerClass, "$actionName:after", $response->getType('json'));

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

	static public function getModule() {
		return $_GET['module'] ?? '';
	}

	static public function dispatch() {
		$module   = $_GET['module'] ?? $_POST['module'] ?? null;
		$action   = $_GET['action'] ?? $_POST['action'] ?? null;
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
			$uri = substr($uri, strlen(V_SUBDIR_INSTALL));
		}

		$uri   = preg_replace('/\?.*$/', '', $uri);
		$route = false;

		if (! $module && (APP != 'admin' && APP != 'install' && (Routes::init(APP) && $route = Routes::match($uri)))) {
			$_GET = array_merge($route, $_GET);
		} else {
			$module         = $module ?? ((APP == 'install' || APP == 'admin') ? 'index' : 'error404');
			self :: $status = 404;
		}

		if ($route) {
			if (preg_match('@(^.+?)/(\w+$)@', $_GET['module'], $routeMatch)) {
				$module = $routeMatch[1];
				$action = $action ?? $routeMatch[2];
			} else {
				$module = trim($_GET['module'], '/');
			}
		}

		if (empty($action)) {
			$action = 'index';
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
