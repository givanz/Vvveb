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

class Cache {
	/** @var object Cache driver instance. */
	private object $driver;

	/** @var int Default cache expiry time in seconds. */
	private int $expire;

	/** @var array<int|string, mixed> In-memory cache buffer. */
	private array $buffer = [];

	/**
	 * Get singleton instance of the Cache class.
	 *
	 * @return self
	 */
	public static function getInstance(): self {
		static $inst = null;

		if ($inst === null) {
			$driver = \Vvveb\config('app.cache.driver', 'file');
			$inst   = new self($driver);
		}

		return $inst;
	}

	/**
	 * Initialize the cache driver.
	 *
	 * @param string $driver Cache driver name.
	 * @param int    $expire Default expiry time in seconds.
	 *
	 * @throws \Exception If the driver class does not exist.
	 *
	 * @return object The instantiated cache driver.
	 */
	public function __construct(string $driver, int $expire = 3600) {
		$class = '\Vvveb\System\Cache\\' . $driver;

		$this->expire = $expire;

		//if (class_exists($class)) {
		$options      = \Vvveb\config(APP . '.cache', []);
		$this->driver = new $class($options);
		//} else {
		//throw new \Exception("Error loading cache driver $driver!");
		//}
	}

	/**
	 * Retrieve a cached value by namespace and key.
	 *
	 * @param string $namespace Cache namespace.
	 * @param string $key       Cache key.
	 *
	 * @return mixed The cached value, or null if not found.
	 */
	public function get(string $namespace, string $key): mixed {
		return $this->buffer[$namespace][$key] ??
			$this->buffer[$namespace][$key] = $this->driver->get($namespace, $key);
	}

	/**
	 * Store a value in the cache.
	 *
	 * @param string $namespace Cache namespace.
	 * @param string $key       Cache key.
	 * @param mixed  $value     Value to store.
	 * @param int    $expire    Expiry time in seconds, 0 uses the default.
	 *
	 * @return bool True on success, false on failure.
	 */
	public function set(string $namespace, string $key, mixed $value, int $expire = 0): bool {
		$expire = $expire ?: $this->expire;

		if (isset($this->buffer[$namespace][$key])) {
			$this->buffer[$namespace][$key] = $value;
		}

		return $this->driver->set($namespace, $key, $value, $expire);
	}

	/**
	 * Cache the results of a callback. Returns the cached value if it exists,
	 * otherwise invokes the callback and stores the result.
	 *
	 * @param string   $namespace Cache namespace.
	 * @param string   $key       Cache key.
	 * @param callable $callback  Callback to invoke when cache misses.
	 * @param int      $expire    Expiry time in seconds, 0 uses the default.
	 *
	 * @return mixed The cached or freshly computed value.
	 */
	public function cache(string $namespace, string $key, callable $callback, int $expire = 0): mixed {
		if (($value = $this->driver->get($namespace, $key)) !== null) {
			return $value;
		}

		$value = $callback();

		$expire = $expire ?: $this->expire;

		if ($this->driver->set($namespace, $key, $value, $expire)) {
		}

		return $value;
	}

	/**
	 * Retrieve multiple cached values by namespace and keys.
	 *
	 * @param string                    $namespace Cache namespace.
	 * @param array<int|string, string> $keys      Array of cache keys.
	 * @param string|int|false          $serverKey Optional server key for distributed caches.
	 *
	 * @return array<int|string, mixed> Associative array<array-key, mixed> of key => value pairs.
	 */
	public function getMulti(string $namespace, array $keys, string|int|false $serverKey = false): array {
		if (isset($this->buffer[$namespace])) {
			$all    = true;
			$result = [];

			foreach ($keys as $key) {
				if (isset($this->buffer[$namespace][$key])) {
					$result[$key] = $this->buffer[$namespace][$key];
				} else {
					$all = false;
					break;
				}
			}

			if ($all) {
				return $result;
			}
		}

		return $this->driver->getMulti($namespace, $keys, $serverKey);
	}

	/**
	 * Store multiple values in the cache.
	 *
	 * @param string                   $namespace Cache namespace.
	 * @param array<int|string, mixed> $items     Associative array of key => value pairs.
	 * @param int                      $expire    Expiry time in seconds, 0 uses the default.
	 * @param string|int|false         $serverKey Optional server key for distributed caches.
	 *
	 * @return bool True on success, false on failure.
	 */
	public function setMulti(string $namespace, array $items, int $expire = 0, string|int|false $serverKey = false): bool {
		$expire = $expire ?: $this->expire;

		$this->buffer[$namespace] = ($this->buffer[$namespace] ?? []) + $items;

		return $this->driver->setMulti($namespace, $items, $expire, $serverKey);
	}

	/**
	 * Delete a cached value or an entire namespace from the cache.
	 *
	 * @param string       $namespace Cache namespace.
	 * @param string|false $key       Cache key to delete, or false to delete the entire namespace.
	 *
	 * @return bool True on success, false on failure.
	 */
	public function delete(string $namespace, string|false $key = false): bool {
		if ($key) {
			unset($this->buffer[$namespace][$key]);
		} else {
			unset($this->buffer[$namespace]);
		}

		return $this->driver->delete($namespace, $key);
	}

	/**
	 * Purge all cached data for a namespace or the entire cache.
	 *
	 * @param string|null $namespace Cache namespace to purge, or null for all.
	 *
	 * @return bool True on success, false on failure.
	 */
	public function purge(?string $namespace = null): bool {
		if ($namespace) {
			unset($this->buffer[$namespace]);
		} else {
			$this->buffer = [];
		}

		return $this->driver->delete($namespace);
	}
}
