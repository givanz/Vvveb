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

namespace Vvveb\Controller;

use \Vvveb\System\Core\FrontController;
use function Vvveb\__;
use function Vvveb\config;
use function Vvveb\siteSettings;
use Vvveb\System\Core\Request;
use Vvveb\System\Core\Response;
use Vvveb\System\Event;
use Vvveb\System\Extensions\Plugins;
use Vvveb\System\Session;
use Vvveb\System\Traits\Permission;
use Vvveb\System\User\Admin;

#[\AllowDynamicProperties]
class Base {
	use Permission;

	protected $global = [];

	function auth() {
		$admin = Admin::current();

		if (! $admin) {
			$authMode = config(APP . '.auth.mode');

			if ($authMode == 'http') {
				if (isset($_SERVER['PHP_AUTH_USER'])) {
					$user      = $_SERVER['PHP_AUTH_USER'];
					$password  = $_SERVER['PHP_AUTH_PW'] ?? '';
					$loginData = compact('user', 'password');

					if ($userInfo = Admin::login($loginData)) {
					} else {
						$this->response->addHeader('WWW-Authenticate', 'Basic realm="REST Api"');
						$this->response->addHeader('HTTP/1.0 401 Unauthorized');
						FrontController::notFound(false, __('Auth failed!'), 403);
					}
				} else {
					$this->response->addHeader('WWW-Authenticate', 'Basic realm="REST Api"');
					$this->response->addHeader('HTTP/1.0 401 Unauthorized');
					FrontController::notFound(false, __('Auth failed!'), 403);
				}
			}
		}
	}

	protected function initEcommerce($countryId, $regionId) {
		$tax = \Vvveb\System\Cart\Tax::getInstance();
		$tax->setRegionRules($countryId, $regionId, 'store');
	}

	function init() {
		$this->response = Response::getInstance();
		$this->request  = Request::getInstance();
		$this->session  = Session::getInstance();

		$this->response->setType('json');

		if (! REST) {
			die($this->notFound(__('REST is disabled!'), 404));
		}

		$this->auth();

		//alow method override via get parameter to avoid issues with unsupported put and patch on some webservers
		$method     = $this->request->get['_method'] ?? $this->request->getMethod();

		$permission = str_replace('/rest/', '', ($this->request->get['route'] ?? '') . '/' . $method);
		//Check permissions
		$className = get_class($this);

		if ($className != 'Vvveb\Controller\Error403') {
			$this->permission($permission);
		}

		Plugins :: loadPlugins();
		$site = siteSettings();

		list($site) = Event::trigger(__CLASS__, __FUNCTION__, $site);

		$this->global['site_id']       = SITE_ID ?? 1;
		$this->global['language_id']   = $this->session->get('language_id') ?? 1;
		$this->global['limit']         = 10;
		$this->global['start']         = 0;
		$this->global['user_id']       = $user['user_id'] ?? false;
		$this->global['user_group_id'] = $user['user_group_id'] ?? 1;
		$this->global['site']          = &$site;
		$this->global['user']          = $user ?? [];

		if (isset($site['country_id'])) {
			$this->initEcommerce($site['country_id'], $site['region_id']);
		}

		$this->global['site'] = &$site;

		list($this->global) = Event::trigger(__CLASS__, __FUNCTION__ . ':after', $this->global);

		if (in_array($method, ['post', 'put', 'delete', 'patch'])) {
			return $method;
		}
	}

	/**
	 * Call this function if the requeste information was not found, for example if the specifed news, image, profile etc is not found then call this function.
	 * It shows a "Not found" page and it also send 404 http status code, this is usefull for search engines etc.
	 *
	 * @param unknown_type $code
	 * @param mixed $service
	 * @param mixed $statusCode
	 * @param null|mixed $message
	 */
	protected function notFound($message = null, $statusCode = 404, $service = false) {
		$response = Response::getInstance();
		http_response_code($statusCode);

		return $response->output(is_array($message) ? $message : ['message' => $message]);
	}
}
