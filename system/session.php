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

namespace Vvveb\System;

class Session {
	private $driver;

	public static function getInstance() {
		static $inst = null;

		if ($inst === null) {
			$driver = \Vvveb\config(APP . '.session.driver', 'php');

			if ($driver) {
				$inst   = new Session($driver);
			}
		}

		return $inst;
	}

	public function __construct($driver, $expire = 3600) {
		$class = '\\Vvveb\\System\\Session\\' . $driver;

		if (class_exists($class)) {
			$options      = \Vvveb\config(APP . '.session', []);
			$this->driver = new $class($options);
		} else {
			throw new \Exception('Error loading session driver ' . $driver);
		}

		return $this->driver;
	}

	public function get($key) {
		return $this->driver ? $this->driver->get($key) : null;
	}

	public function set($key, $value) {
		return $this->driver ? $this->driver->set($key, $value) : null;
	}

	public function delete($key) {
		return $this->driver ? $this->driver->delete($key) : null;
	}

	public function close() {
		return $this->driver ? $this->driver->close() : null;
	}

	public function sessionId($id = null) {
		return $this->driver ? $this->driver->sessionId($id) : null;
	}

	public function regenerateId($deleteOldSession = false) {
		return $this->driver ? $this->driver->regenerateId($deleteOldSession) : null;
	}
}
