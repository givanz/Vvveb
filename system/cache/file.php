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

use function Vvveb\sanitizeFileName;

/**
 * File-system-based cache driver.
 *
 * Stores cached values as JSON-encoded files in a configurable directory.
 * Each file is named by namespace, optional prefix, and key. Supports
 * namespace/key validation, multi-get/set, expired-file purging, and
 * exclusive file locking for concurrent writes.
 */
class File {
	// CacheTrait provides stats() and a base delete() implementation.
	use CacheTrait;

	/** @var int Default cache lifetime in seconds. */
	private int $expire = 3600;

	/** @var array<string, mixed> Configuration options merged in the constructor. */
	private array $options = [];

	/** @var string Absolute path to the cache directory. */
	private string $cacheDir = DIR_CACHE;

	/** @var string Optional prefix prepended to cache filenames. */
	private string $cachePrefix = ''; //'cache.';

	/**
	 * Initialize cache settings from the provided options array.
	 *
	 * Supported keys:
	 *   - expire  (int)     Default TTL in seconds (default 3600).
	 *   - dir     (string)  Absolute path to the cache directory.
	 *   - prefix  (string)  Filename prefix for cache files.
	 *
	 * @param array<string, mixed> $options Configuration overrides.
	 */
	public function __construct(array $options) {
		$this->options += $options;

		$this->expire      = $this->options['expire'] ?? $this->expire;
		$this->cacheDir    = $this->options['dir'] ?? $this->cacheDir;
		$this->cachePrefix = $this->options['prefix'] ?? $this->cachePrefix;
	}

	/**
	 * Sanitise a namespace or key string for use as part of a filename.
	 *
	 * Replaces directory separators with dots and applies the global
	 * sanitizeFileName filter. Empty values are returned as-is.
	 *
	 * @param string $namespace Raw namespace or key value.
	 *
	 * @return string Sanitised filename-safe string, or empty string if input was falsy.
	 */
	protected function validateNamespace(string $namespace): string {
		if ($namespace) {
			$namespace = str_replace(['\\', '/'], '.', $namespace);
			$namespace = sanitizeFileName($namespace);
		}

		return $namespace;
	}

	/**
	 * Retrieve a single cached value.
	 *
	 * Looks up the file for the given namespace and key, reads its contents,
	 * and returns the JSON-decoded data.
	 *
	 * Entries store their expiry timestamp as the file modification time,
	 * so expired entries return null without needing a purge pass first.
	 *
	 * @param string $namespace Cache namespace.
	 * @param string $key       Cache key.
	 *
	 * @return mixed|null The decoded cache value, or null if the file does not exist or has expired.
	 */
	public function get(string $namespace, string $key): mixed {
		$namespace  = $this->validateNamespace($namespace);
		$key        = $this->validateNamespace($key);

		$file = $this->cacheDir . $namespace . '.' . $this->cachePrefix . basename($key);

		if (file_exists($file)) {
			//mtime holds the expiry timestamp, fail closed when it cannot be read
			if ((@filemtime($file) ?: 0) <= time()) {
				return null;
			}

			$data = @file_get_contents($file);

			if ($data === false) {
				return null;
			}

			return json_decode($data, true);
		}

		return null;
	}

	/**
	 * Store a value in the file cache.
	 *
	 * The value is JSON-encoded and written with exclusive file locking
	 * (LOCK_EX). The file's modification time is set to the expiry timestamp
	 * so that get() and purgeExpired() can detect stale entries.
	 *
	 * @param string   $namespace Cache namespace.
	 * @param string   $key       Cache key.
	 * @param mixed    $value     The value to cache (will be JSON-encoded).
	 * @param int|null $expire    Lifetime in seconds, null to use the default.
	 *                            Zero or negative values expire immediately.
	 *
	 * @return bool True on success, false if the file could not be opened for writing.
	 */
	public function set(string $namespace, string $key, mixed $value, ?int $expire = null): bool {
		$namespace  = $this->validateNamespace($namespace);
		$key        = $this->validateNamespace($key);

		if ($expire === null) {
			$expire = $this->expire;
		}
		$expire = time() + $expire;

		//$this->delete($namespace, $key);
		$file = $this->cacheDir . $namespace . '.' . $this->cachePrefix . basename($key);

		$handle = @fopen($file, 'w');

		if ($handle) {
			flock($handle, LOCK_EX);
			fwrite($handle, json_encode($value/*, JSON_PRETTY_PRINT*/));
			fflush($handle);
			flock($handle, LOCK_UN);
			fclose($handle);
			//set mtime to expiry timestamp so purgeExpired() can detect stale entries
			//must be done after fclose, otherwise fopen('w') resets mtime to current time
			@touch($file, $expire);

			return true;
		}

		return false;
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
	 * Store multiple values in the file cache.
	 *
	 * @param string               $namespace Cache namespace.
	 * @param array<string, mixed> $items     Associative array of key => value pairs.
	 * @param int|null             $expire    Lifetime in seconds, or null to use the default.
	 * @param string|int|false     $serverKey Unused (reserved for interface compatibility).
	 *
	 * @return bool Always returns true.
	 */
	public function setMulti(string $namespace, array $items, ?int $expire = null, string|int|false $serverKey = false): bool {
		$namespace = $this->validateNamespace($namespace);

		foreach ($items as $key => $value) {
			$this->set($namespace, $key, $value, $expire);
		}

		return true;
	}

	/**
	 * Purge cache entries by deleting matching files.
	 *
	 * Alias for {@see delete()}.
	 *
	 * @param string $namespace Cache namespace to purge, or empty string for all.
	 *
	 * @return bool True if all files were deleted successfully, false otherwise.
	 */
	public function purge(string $namespace = ''): bool {
		$this->delete($namespace);

		return true;
	}

	/**
	 * Delete cached entries matching a namespace and optional key.
	 *
	 * When a key is provided, deletes that specific cache file. When only a
	 * namespace is provided, deletes all files in that namespace. When neither
	 * is provided, deletes all cache files in the cache directory.
	 *
	 * Hidden files are never matched (glob('*') excludes dotfiles).
	 *
	 * @param string       $namespace Cache namespace, or empty string for all.
	 * @param string|false $key       Specific cache key to delete, or false for all in namespace.
	 *
	 * @return bool True if all files were deleted successfully, false if any deletion failed.
	 */
	public function delete(string $namespace, string|false $key = false): bool {
		$namespace = $this->validateNamespace($namespace);

		$name = $this->cacheDir;

		if ($namespace) {
			$name .= $namespace . '.';
		}

		if ($key) {
			$name .= $this->cachePrefix . basename($key) . '*';
		} else {
			$name .= '*';
		}

		$files   = glob($name);
		$success = true;

		if ($files) {
			foreach ($files as $file) {
				if (!@unlink($file)) {
					$success = false;
				}
			}
		}

		return $success;
	}

	/**
	 * Remove all expired cache files from the cache directory.
	 *
	 * Files store their expiry timestamp as modification time (see set()),
	 * so a file is purged once it has been expired for longer than $expired
	 * seconds. Pass 0 to remove everything that is already expired.
	 * Hidden files (starting with a dot) are skipped.
	 *
	 * @param int $expired Grace period in seconds after expiry before removal.
	 *
	 * @return void
	 */
	public function purgeExpired(int $expired = 86400): void {
		//the directory may not exist yet on fresh installs or custom paths
		if (!is_dir($this->cacheDir)) {
			return;
		}

		if ($files = (scandir($this->cacheDir))) {
			ignore_user_abort(true);

			foreach ($files as $file) {
				if ($file[0] === '.') {
					continue;
				}

				$file = $this->cacheDir . $file;

				if (file_exists($file)) {
					$time = filemtime($file);

					if (($time + $expired) < time()) {
						@unlink($file);
					}
				}
			}
		}
	}

	/**
	 * Randomly trigger expired-cache cleanup on object destruction.
	 *
	 * There is a 1-in-1000 chance that purgeExpired() is called each time
	 * a File cache instance is destroyed, providing probabilistic garbage
	 * collection without requiring a cron job.
	 *
	 * @return void
	 */
	public function __destruct() {
		if (random_int(1, 1000) === 1) {
			$this->purgeExpired();
		}
	}
}
