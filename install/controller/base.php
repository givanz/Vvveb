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

use Vvveb\System\Core\FrontController;
use Vvveb\System\Core\Request;
use Vvveb\System\Core\Response;
use Vvveb\System\Core\View;
use Vvveb\System\Session;

#[\AllowDynamicProperties]
class Base {
	/** @var Request Request instance injected by FrontController DI. */
	public Request $request;

	/** @var Response Response instance injected by FrontController DI. */
	public Response $response;

	/** @var View View instance injected by FrontController DI. */
	public View $view;

	/** @var Session Session instance injected by FrontController DI. */
	public Session $session;

	public function __construct() {
		$this->request = Request::getInstance();
		$this->view    = View::getInstance();
		$this->session = Session::getInstance();
	}

	/**
	 * Redirect to the given URL and terminate.
	 *
	 * @param string $url The URL to redirect to.
	 *
	 * @return void
	 */
	public function redirect(string $url = '/'): void {
		if (defined('CLI')) {
			return;
		}

		session_write_close();
		header("Location: $url");
		exit();
	}

	/**
	 * Show a 404 not-found response.
	 *
	 * @param bool        $service    Whether this is a service request.
	 * @param string|null $message    Optional error message.
	 * @param int         $statusCode HTTP status code.
	 *
	 * @return mixed
	 */
	public function notFound(bool $service = false, ?string $message = null, int $statusCode = 404): mixed {
		return FrontController::notFound($service, $message, $statusCode);
	}
}
