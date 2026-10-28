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
	/** @var object Session driver instance. */
	private object $driver;

	/**
	 * Get singleton instance of the Session class.
	 *
	 * @param array<string, mixed> $options Additional session options.
	 *
	 * @return self|null The singleton instance, or null if no driver is configured.
	 */
	public static function getInstance(array $options = []): ?self {
		static $inst = null;

		if ($inst === null) {
			$driver = \Vvveb\config(APP . '.session.driver', 'php');

			if ($driver) {
				$inst   = new self($driver, $options);
			}
		}

		return $inst;
	}

	/**
	 * Initialize the session driver.
	 *
	 * @param string               $driver Session driver name.
	 * @param array<string, mixed> $opts   Additional options merged with config.
	 *
	 * @return object The instantiated session driver.
	 */
	public function __construct(string $driver, array $opts = []) {
		$class = '\Vvveb\System\Session\\' . $driver;

		//if (class_exists($class)) {
		$options  = \Vvveb\config(APP . '.session', []);
		$options += $opts;
		$this->driver = new $class($options);
		/*
		} else {
			throw new \Exception('Error loading session driver ' . $driver);
		}*/
	}

	/**
	 * Retrieve a session value by key.
	 *
	 * @param string $key Session key.
	 *
	 * @return mixed The session value, or null if not found.
	 */
	public function get(string $key): mixed {
		return $this->driver->get($key);
	}

	/**
	 * Store a value in the session.
	 *
	 * @param string $key   Session key.
	 * @param mixed  $value Value to store.
	 *
	 * @return mixed The result of the driver set operation.
	 */
	public function set(string $key, mixed $value): mixed {
		return $this->driver->set($key, $value);
	}

	/**
	 * Delete a session value by key.
	 *
	 * @param string $key Session key.
	 *
	 * @return mixed The result of the driver delete operation.
	 */
	public function delete(string $key): mixed {
		return $this->driver->delete($key);
	}

	/**
	 * Close the session.
	 *
	 * @return mixed The result of the driver close operation.
	 */
	public function close(): mixed {
		return $this->driver->close();
	}

	/**
	 * Get or set the current session ID.
	 *
	 * @param string|null $id Session ID to set, or null to retrieve the current one.
	 *
	 * @return mixed The session ID.
	 */
	public function sessionId(?string $id = null): mixed {
		return $this->driver->sessionId($id);
	}

	/**
	 * Regenerate the session ID, optionally deleting the old session.
	 *
	 * @param bool $deleteOldSession Whether to delete the old session file.
	 *
	 * @return mixed The result of the driver regenerateId operation.
	 */
	public function regenerateId(bool $deleteOldSession = false): mixed {
		return $this->driver->regenerateId($deleteOldSession);
	}
}
