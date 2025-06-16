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

namespace Vvveb\System\Session;

/**
 * Session placeholder if no driver is provided.
 * Used for apps like GraphQl where session is handled externally.
 */
class Request {
	private $data;

	public function __construct($options) {
	}

	public function __destruct() {
	}

	public function get($key) {
		if (isset($this->data[$key])) {
			return $this->data[$key];
		}
	}

	public function set($key, $value) {
		$this->data[$key] = $value;
	}

	public function delete($key) {
		unset($this->data[$key]);
	}

	public function close() {
	}

	public function sessionId($id = null) {
	}

	public function regenerateId($deleteOldSession = false) {
	}

	public function gc() {
		$this->data = [];

		return true;
	}
}
