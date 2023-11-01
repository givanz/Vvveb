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

use \Vvveb\System\Core\Request as Request;
use Vvveb\System\Core\View;

#[\AllowDynamicProperties]
class Base {
	function __construct() {
		$this->request = Request::getInstance();
		$this->view    = View::getInstance();
	}

	function redirect($url = '/') {
		session_write_close();

		return die(header("Location: $url"));
	}

	function notFound($service = false, $message = null,  $statusCode = 404) {
		return FrontController::notFound($service, $message, $statusCode);
	}
}
