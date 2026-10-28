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

#[\AllowDynamicProperties]
class Error404 extends Base {
	/**
	 * Return a 404 error response for MCP.
	 *
	 * @return array{error: array{message: string, code: int}, file?: string}
	 */
	public function index(): array {
		$this->response->setType('json');

		$return = ['error' => ['message' => $this->view->message ?? 'Route not found!', 'code' => $this->view->code ?? -32404]];

		if (DEBUG) {
			if (isset($this->view->file)) {
				$return['file'] = $this->view->file;
			}
		}

		return $return;
	}
}
