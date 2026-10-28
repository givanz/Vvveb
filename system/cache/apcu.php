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

class APCu {
	/** @var int Default cache lifetime in seconds. */
	private int $expire = 0;

	/** @var array<string, mixed> Configuration options merged with defaults. */
	private array $options = [];

	/** @var bool Whether APCu is available and enabled. */
	private bool $active;

	/** @var string Cache key prefix (CRC32 of DIR_ROOT for shared-hosting isolation). */
	private string $cachePrefix = '';

	/**
	 * @param array<string, mixed> $options Configuration options: expire (int).
	 */
	public function __construct(array $options) {
		$this->options += $options;

		$this->cachePrefix = sprintf('%d', crc32(DIR_ROOT)); //unique instance for shared hosting
		$this->expire      = $this->options['expire'] ?? $this->expire;
		$this->active      = function_exists('apcu_cache_info') && ini_get('apc.enabled');
	}

	/**
	 * Build the full APCu cache key from namespace and key.
	 *
	 * @param string $namespace Cache namespace.
	 * @param string $key       Cache key.
	 *
	 * @return string The composite cache key with directory separators replaced by dots.
	 */
	private function key(string $namespace, string $key = ''): string {
		return str_replace(['\\', '/'], '.', $this->cachePrefix . $namespace . '.' . $key);
	}

	/**
	 * Retrieve a cached value by namespace and key.
	 *
	 * @param string $namespace Cache namespace.
	 * @param string $key       Cache key.
	 *
	 * @return mixed|null The cached value, or null if not found or APCu is inactive.
	 */
	public function get(string $namespace, string $key): mixed {
		$success = false;
		$result  = $this->active ? apcu_fetch($this->key($namespace, $key), $success) : null;

		if ($success) {
			return $result;
		}

		return null;
	}

	/**
	 * Store a value in the APCu cache.
	 *
	 * @param string   $namespace Cache namespace.
	 * @param string   $key       Cache key.
	 * @param mixed    $value     The value to cache.
	 * @param int|null $expire    Lifetime in seconds, or null to use the default.
	 *
	 * @return bool True on success, false when APCu is inactive or storage failed.
	 */
	public function set(string $namespace, string $key, mixed $value, ?int $expire = null): bool {
		if (!$expire) {
			$expire = $this->expire;
		}

		if ($this->active) {
			return apcu_store($this->key($namespace, $key), $value, $expire);
		}

		return false;
	}

	/**
	 * Retrieve multiple cached values by namespace.
	 *
	 * Missing keys map to null; never fails, even when APCu is inactive.
	 *
	 * @param string             $namespace Cache namespace.
	 * @param array<int, string> $keys      List of cache keys to retrieve.
	 * @param string|int|false   $serverKey Unused (reserved for interface compatibility).
	 *
	 * @return array<string, mixed|null> Associative array<array-key, mixed> of originalKey => value.
	 */
	public function getMulti(string $namespace, array $keys, string|int|false $serverKey = false): array {
		$fullKeys = [];

		foreach ($keys as $key) {
			$fullKeys[$key] = $this->key($namespace, $key);
		}

		$fetched = $this->active ? apcu_fetch(array_values($fullKeys), $success) : false;

		if (!is_array($fetched)) {
			$fetched = [];
		}

		$output = [];

		foreach ($fullKeys as $key => $fullKey) {
			$output[$key] = $fetched[$fullKey] ?? null;
		}

		return $output;
	}

	/**
	 * Store multiple values in the APCu cache.
	 *
	 * @param string               $namespace Cache namespace.
	 * @param array<string, mixed> $items     Associative array of key => value pairs.
	 * @param int|null             $expire    Lifetime in seconds, or null to use the default.
	 * @param string|int|false     $serverKey Unused (reserved for interface compatibility).
	 *
	 * @return bool True on success.
	 */
	public function setMulti(string $namespace, array $items, ?int $expire = null, string|int|false $serverKey = false): bool {
		foreach ($items as $key => $value) {
			$this->set($namespace, $key, $value, $expire);
		}

		return true;
	}

	/**
	 * Delete cached entries matching a namespace and optional key.
	 *
	 * When a key is provided, deletes matching entries via APCUIterator regex.
	 * When only a namespace is provided, deletes all entries in that namespace.
	 * When neither is provided, clears the entire APCu cache.
	 *
	 * @param string       $namespace Cache namespace.
	 * @param string|false $key       Cache key to delete, or false for all in namespace.
	 *
	 * @return bool True when entries were deleted or the whole cache was cleared, false when inactive.
	 */
	public function delete(string $namespace, string|false $key = false): bool {
		if ($this->active) {
			if ($namespace) {
				//anchor and quote the key: APCUIterator matches unanchored PCRE,
				//so unescaped dots would also match sibling namespaces
				apcu_delete(new \APCUIterator('/^' . preg_quote($this->key($namespace, $key ?: ''), '/') . '/'));

				return true;
			} else {
				return apcu_clear_cache();
			}
		}

		return false;
	}

	/**
	 * Purge the entire APCu cache.
	 *
	 * @param string $namespace Unused (kept for interface compatibility).
	 *
	 * @return bool True if the cache was cleared successfully, false otherwise.
	 */
	public function purge(string $namespace = ''): bool {
		$status = false;

		if (function_exists('apcu_clear_cache')) {
			$status = apcu_clear_cache();
		}

		return $status;
	}
}
