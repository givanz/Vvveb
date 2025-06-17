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
class Error500 extends Base {
	function index() {
		$this->response->setType('json');

		if (DEBUG) {
			return [
				'message'   => $this->view->message,
				'file'      => $this->view->file,
				'line_no'   => $this->view->line_no,
				'line'      => $this->view->line,
				'lines'     => $this->view->lines,
				'trace'     => $this->view->trace,
				'codeLines' => $this->view->code,
				'code'      => 500,
			];
		} else {
			return ['message' => 'Internal server error!', 'code' => 500];
		}
	}
}
