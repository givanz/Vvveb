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

use function Vvveb\__;
use function Vvveb\config;
use function Vvveb\siteSettings;
use Vvveb\System\Core\Request;
use Vvveb\System\Core\Response;
use Vvveb\System\Event;
use Vvveb\System\Session;
use Vvveb\System\Traits\Permission;
use Vvveb\System\User\Admin;

#[\AllowDynamicProperties]
class Base {
	use Permission;

	/** @var array<string, mixed> */
	protected array $global = [];

	/**
	 * Authenticate the current admin user via HTTP or token-based auth.
	 *
	 * @return void
	 */
	public function auth(): void {
		$admin = Admin::current();

		if (!$admin) {
			$authMode = config('mcp.auth.mode');

			if ($authMode == 'http') {
				if (isset($_SERVER['PHP_AUTH_USER'])) {
					$user      = $_SERVER['PHP_AUTH_USER'];
					$password  = $_SERVER['PHP_AUTH_PW'] ?? '';
					$loginData = compact('user', 'password');
					$feedback  = [];

					if ($userInfo = Admin::login($loginData, [], $feedback)) {
					} else {
						$this->response->addHeader('WWW-Authenticate', 'Basic realm="REST Api"');
						$this->response->addHeader('HTTP/1.0 401 Unauthorized');
						$message = __('Auth failed!');

						if (DEBUG) {
							$message .= ' ' . ($feedback['message'] ?? '');
							$message .= " auth.mode = $authMode";
						}
						$this->notFound($message, 403);
					}
				} else {
					$this->response->addHeader('WWW-Authenticate', 'Basic realm="REST Api"');
					$this->response->addHeader('HTTP/1.0 401 Unauthorized');
					$message = __('Auth failed!');

					if (DEBUG) {
						$message .= __('No credentials!');
						$message .= " auth.mode = $authMode";
					}
					$this->notFound($message, 403);
				}
			} else {
				if ($authMode == 'token') {
					$headers = getallheaders();
					$token   = $headers['Bearer'] ?? $_SERVER['HTTP_BEARER'] ?? null;
					if (!$token) {
						$token = $headers['Authorization'] ?? $_SERVER['HTTP_AUTHORIZATION'] ?? '';
						$token = trim(substr($token, 6)) ?: $this->request->get['_token'] ?? false;
					}

					if ($token) {
						$feedback = [];
						if ($userInfo = Admin::auth($token, [], $feedback)) {
						} else {
							$this->response->addHeader('HTTP/1.0 401 Unauthorized');
							$message = __('Auth failed!');

							if (DEBUG) {
								$message .= ' ' . ($feedback['message'] ?? '');
								$message .= " auth.mode = $authMode";
								$message .= " token = $token";
							}
							$this->notFound($message, 403);
						}
					} else {
						$this->response->addHeader('HTTP/1.0 401 Unauthorized');
						$message = __('Auth failed!');

						if (DEBUG) {
							$message .= __('No credentials!');
							$message .= " auth.mode = $authMode";
							$message .= " token = $token";
						}
						$this->notFound($message, 403);
					}
				} else {
					//fail closed: deny any auth mode that is not explicitly enabled
					$this->response->addHeader('HTTP/1.0 401 Unauthorized');
					$message = __('Auth failed!');

					if (DEBUG) {
						$message .= __('No auth mode configured!');
						$message .= " auth.mode = $authMode";
					}
					$this->notFound($message, 403);
				}
			}
		}
	}

	/**
	 * Initialize ecommerce tax rules for the given region.
	 *
	 * @param int $countryId
	 * @param int $regionId
	 *
	 * @return void
	 */
	protected function initEcommerce(int $countryId, int $regionId): void {
		$tax = \Vvveb\System\Cart\Tax::getInstance();
		$tax->setRegionRules($countryId, $regionId, 'store');
	}

	/**
	 * Initialize the MCP controller: set up request/response, authenticate, and load global settings.
	 *
	 * @return void
	 */
	public function init(): void {
		$this->response = Response::getInstance();
		$this->request  = Request::getInstance();
		$this->session  = Session::getInstance();

		$this->response->setType('json');

		if (!MCP) {
			$this->notFound(__('MCP is disabled!'), 404);
		}

		$this->auth();
		$site = siteSettings();

		[$site] = Event::trigger(__CLASS__, __FUNCTION__, $site);

		$this->global['site_id']             = SITE_ID;
		$this->global['admin_id']            = 1;
		$this->global['languages']           = $site['languages'] ?? [];
		$this->global['language']            = ($site['language'] ?? '') ?: 'en';
		$this->global['language_id']         = $this->session->get('language_id') ?? $site['language_id'] ?? 1;
		$this->global['default_language']    = $site['language'] ?? 'en';
		$this->global['default_language_id'] = $this->session->get('default_language_id') ?? $site['language_id'] ?? 1;
		$this->global['default_lang_slug']   = $site['default_lang_slug'] ?? false;
		$this->global['currency_id']         = (int)($this->session->get('currency_id') ?? $site['currency_id'] ?? 1);
		$this->global['currency']            = $this->session->get('currency') ?? $site['currency'] ?? 'usd';
		$this->global['currencies']          = $site['currencies'] ?? [];
		$this->global['limit']               = 10;
		$this->global['start']               = 0;
		$this->global['user_id']             = false;
		$this->global['user_group_id']       = 1;
		$this->global['site']                = &$site;
		$this->global['user']                = [];

		$language_id = $this->global['language_id'];

		if (isset($site['description'][$language_id])) {
			$site['description'] = $site['description'][$language_id];
		}

		if (isset($site['country_id'])) {
			$this->initEcommerce($site['country_id'], $site['region_id']);
		}

		$this->global['site'] = &$site;

		[$this->global] = Event::trigger(__CLASS__, __FUNCTION__ . ':after', $this->global);
	}

	/**
	 * Call this function if the requested information was not found.
	 * Outputs a JSON-RPC error response and terminates.
	 *
	 * @param array<string, mixed>|string|null $message
	 * @param int                              $statusCode
	 * @param bool                             $service
	 *
	 * @return never
	 */
	protected function notFound(array|string|null $message = null, int $statusCode = -32404, bool $service = false): never {
		$this->response->setType('json');

		$output = [
			'jsonrpc' => '2.0',
			'id'      => '0',
			'error'   => [
				'message' => $message,
				'code'    => $statusCode,
			]
		];

		$output['error']['message'] = $message;
		$output['error']['code']    = $statusCode;

		$this->response->output($output);

		exit();
	}
}
