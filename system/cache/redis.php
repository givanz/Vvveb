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

class Redis {
	/** @var int Default cache lifetime in seconds. */
	private int $expire = 0;

	/** @var \Redis The underlying Redis connection instance. */
	private \Redis $redis;

	/** @var string Cache key prefix. */
	private string $cachePrefix = ''; //'cache.';

	/** @var array<string, mixed> Default configuration options. */
	private array $options = ['expire' => 3000, 'prefix' => 'vvveb.'];

	/**
	 * @param array<string, mixed> $options Configuration options: host (string), port (int),
	 *                                      password (string, optional), expire (int), prefix (string).
	 */
	public function __construct(array $options) {
		//user options take precedence over the defaults
		$this->options = $options + $this->options;

		$this->expire      = $this->options['expire'] ?? $this->expire;
		$this->cachePrefix = $this->options['prefix'] ?? $this->cachePrefix;

		if (empty($this->options['host'])) {
			throw new \InvalidArgumentException('Redis cache requires a host option');
		}

		$this->redis = new \Redis();
		$this->redis->pconnect($this->options['host'], $this->options['port'] ?? 6379);

		if (isset($this->options['password'])) {
			$this->redis->auth($this->options['password']);
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
	 * @return mixed|null The decoded cached value, or null if not found.
	 */
	public function get(string $namespace, string $key): mixed {
		$data = $this->redis->get($this->key($namespace, $key));

		return json_decode($data, true);
	}

	/**
	 * Store a value in the Redis cache.
	 *
	 * Values are JSON-encoded before storage. If an expiry is specified,
	 * a TTL is set on the key after writing.
	 *
	 * @param string   $namespace Cache namespace.
	 * @param string   $key       Cache key.
	 * @param mixed    $value     The value to cache (will be JSON-encoded).
	 * @param int|null $expire    Lifetime in seconds, or null to use the default.
	 *
	 * @return bool True on success, false on failure.
	 */
	public function set(string $namespace, string $key, mixed $value, ?int $expire = null): bool {
		$expire ??= $this->expire;
		$_key   = $this->key($namespace, $key);

		if ($expire) {
			return (bool)$this->redis->set($_key, json_encode($value), ['ex' => $expire]);
		}

		return (bool)$this->redis->set($_key, json_encode($value));
	}

	/**
	 * Retrieve multiple cached values by namespace.
	 *
	 * @param string             $namespace Cache namespace.
	 * @param array<int, string> $keys      List of cache keys to retrieve.
	 * @param string|int|false   $serverKey Unused (reserved for interface compatibility).
	 *
	 * @return array<string, mixed|null> Associative array<array-key, mixed> of key => value pairs.
	 */
	public function getMulti(string $namespace, array $keys, string|int|false $serverKey = false): array {
		$result = [];

		foreach ($keys as $key) {
			$result[$key] = $this->get($namespace, $key);
		}

		return $result;
	}

	/**
	 * Store multiple values in the Redis cache.
	 *
	 * @param string               $namespace Cache namespace.
	 * @param array<string, mixed> $items     Associative array of key => value pairs.
	 * @param int|null             $expire    Lifetime in seconds, or null to use the default.
	 * @param string|int|false     $serverKey Unused (reserved for interface compatibility).
	 *
	 * @return bool True on success, false on failure.
	 */
	public function setMulti(string $namespace, array $items, ?int $expire = null, string|int|false $serverKey = false): bool {
		$expire ??= $this->expire;
		$status = true;

		foreach ($items as $key => $value) {
			$status = $this->set($namespace, $key, $value, $expire) && $status;
		}

		return $status;
	}

	/**
	 * Delete cached entries matching a namespace and optional key.
	 *
	 * When a key is provided, deletes that specific entry. When only a namespace
	 * is provided, uses a wildcard pattern to delete all entries in that namespace.
	 * When neither is provided, deletes all cached entries.
	 *
	 * @param string       $namespace Cache namespace.
	 * @param string|false $key       Cache key to delete, or false to delete all in namespace.
	 *
	 * @return bool Always returns true.
	 */
	public function delete(string $namespace, string|false $key = false): bool {
		if ($key) {
			$this->redis->del($this->key($namespace, $key));
		} else {
			$pattern = $namespace ? $this->key($namespace, '*') : $this->key('*');

			//non-blocking iteration, unlike KEYS which blocks the server
			$iterator = null;

			do {
				$keys = $this->redis->scan($iterator, $pattern);

				if ($keys) {
					$this->redis->del($keys);
				}
			} while ($iterator);
		}

		return true;
	}
}
