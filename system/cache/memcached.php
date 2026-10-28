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

namespace Vvveb\System\Cache;

class Memcached {
	/** @var \Memcached The underlying Memcached connection instance. */
	private \Memcached $memcached;

	/** @var string Cache key prefix (CRC32 of DIR_ROOT for shared-hosting isolation). */
	private string $cachePrefix = '';

	/**
	 * Get Memcached server statistics.
	 *
	 * @param bool|int $time Unused (kept for interface compatibility).
	 *
	 * @return array<string, mixed> Server statistics.
	 */
	public function stats(bool|int $time = false): array {
		return $this->memcached->getStats();
	}

	/**
	 * Purge (flush) the entire Memcached cache.
	 *
	 * @param string|false $namespace  Unused (kept for interface compatibility).
	 * @param int          $time_delay Delay in seconds before the flush takes effect.
	 *
	 * @return bool True on success, false on failure.
	 */
	public function purge(string|false $namespace = false, int $time_delay = 0): bool {
		return $this->memcached->flush($time_delay);
	}

	/**
	 * @param array<string, mixed> $options Configuration options: servers (array), options (array).
	 */
	public function __construct(array $options) {
		if (empty($options['servers'])) {
			throw new \InvalidArgumentException('Memcached cache requires a servers option');
		}

		$this->cachePrefix = sprintf('%d', crc32(DIR_ROOT)); //unique instance for shared hosting
		$this->memcached   = new \Memcached();
		$this->memcached->addServers($options['servers']);

		if (isset($options['options'])) {
			foreach ($options['options'] as $key => $value) {
				$this->memcached->setOption($key, $value);
			}
		}
	}

	/**
	 * Build the full cache key from namespace and key.
	 *
	 * @param string $namespace Cache namespace.
	 * @param string $key       Cache key.
	 *
	 * @return string The composite cache key.
	 */
	private function key(string $namespace, string $key = ''): string {
		return $this->cachePrefix . ($namespace ? ".$namespace" : '') . $key;
	}

	/**
	 * Retrieve a cached value by namespace and key.
	 *
	 * @param string $namespace Cache namespace.
	 * @param string $key       Cache key.
	 *
	 * @return mixed The cached value, or false on failure.
	 */
	public function get(string $namespace, string $key): mixed {
		return $this->memcached->get($this->key($namespace, $key));
	}

	/**
	 * Store a value in the Memcached cache.
	 *
	 * @param string $namespace Cache namespace.
	 * @param string $key       Cache key.
	 * @param mixed  $value     The value to cache.
	 * @param int    $expire    Lifetime in seconds, or 0 for no expiry.
	 *
	 * @return bool True on success, false on failure.
	 */
	public function set(string $namespace, string $key, mixed $value, int $expire = 0): bool {
		return $this->memcached->set($this->key($namespace, $key), $value, $expire);
	}

	/**
	 * Retrieve multiple cached values by namespace.
	 *
	 * @param string             $namespace Cache namespace.
	 * @param array<int, string> $keys      List of cache keys to retrieve.
	 * @param string|int|false   $serverKey Server key for consistent hashing, or false for default.
	 *
	 * @return array<string, mixed> Associative array<array-key, mixed> of key => value pairs.
	 */
	public function getMulti(string $namespace, array $keys, string|int|false $serverKey = false): array {
		$prefixed = [];
		$map      = [];

		foreach ($keys as $key) {
			$pkey            = $this->key($namespace, $key);
			$prefixed[]      = $pkey;
			$map[$pkey]      = $key;
		}

		if ($serverKey) {
			$result = $this->memcached->getMultiByKey($serverKey, $prefixed);
		} else {
			$result = $this->memcached->getMulti($prefixed);
		}

		$output = [];

		foreach ($map as $pkey => $origKey) {
			$output[$origKey] = $result[$pkey] ?? null;
		}

		return $output;
	}

	/**
	 * Store multiple values in the Memcached cache.
	 *
	 * @param string               $namespace Cache namespace.
	 * @param array<string, mixed> $items     Associative array of key => value pairs.
	 * @param int                  $expire    Lifetime in seconds, or 0 for no expiry.
	 * @param string|int|false     $serverKey Server key for consistent hashing, or false for default.
	 *
	 * @return bool True on success, false on failure.
	 */
	public function setMulti(string $namespace, array $items, int $expire = 0, string|int|false $serverKey = false): bool {
		$prefixed = [];

		foreach ($items as $key => $value) {
			$prefixed[$this->key($namespace, $key)] = $value;
		}

		if ($serverKey) {
			return $this->memcached->setMultiByKey($serverKey, $prefixed, $expire);
		} else {
			return $this->memcached->setMulti($prefixed, $expire);
		}
	}

	/**
	 * Delete cached entries matching a namespace and optional key.
	 *
	 * Memcached has no wildcard delete, so namespace deletes list all keys
	 * and remove the ones under the namespace prefix.
	 *
	 * @param string       $namespace Cache namespace, or empty string for all entries.
	 * @param string|false $key       Cache key to delete, or false for all in namespace.
	 *
	 * @return bool True on success, false on failure.
	 */
	public function delete(string $namespace, string|false $key = false): bool {
		if ($key) {
			return (bool)$this->memcached->delete($this->key($namespace, $key));
		}

		$allKeys = $this->memcached->getAllKeys();

		if (!is_array($allKeys)) {
			return false;
		}

		$prefix  = $this->key($namespace);
		$matched = array_values(array_filter($allKeys, fn ($k) => str_starts_with((string)$k, $prefix)));

		if ($matched === []) {
			return true;
		}

		$result = $this->memcached->deleteMulti($matched);

		return !in_array(false, $result, true);
	}
}
