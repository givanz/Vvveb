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

class File {
	/* get purge, stats from trait*/
	use CacheTrait;

	private $expire = 3600;

	private $options = [];

	private $cacheDir = DIR_CACHE;

	private $cachePrefix = 'cache.';

	public function __construct($options) {
		$this->options += $options;

		$this->expire      = $this->options['expire'] ?? $this->expire;
		$this->cacheDir    = $this->options['dir'] ?? $this->cacheDir;
		$this->cachePrefix = $this->options['prefix'] ?? $this->cachePrefix;
	}

	protected function validateNamespace($namespace) {
		if ($namespace) {
			$namespace = sanitizeFileName($namespace);
		}

		return $namespace;
	}

	public function get($namespace, $key) {
		$namespace = $this->validateNamespace($namespace);

		$files = glob($this->cacheDir . $namespace . 'cache.' . basename($key) . '.*');

		if ($files) {
			$data = file_get_contents($files[0]);

			return json_decode($data, true);
		}

		return null;
	}

	public function set($namespace, $key, $value, $expire = null) {
		$namespace = $this->validateNamespace($namespace);

		if (! $expire) {
			$expire = $this->expire;
		}
		$expire = time() + $expire;

		$this->delete($namespace, $key);
		$file = $this->cacheDir . $namespace . 'cache.' . basename($key) . '.' . $expire;

		$handle = fopen($file, 'w');
		flock($handle, LOCK_EX);
		fwrite($handle, json_encode($value/*, JSON_PRETTY_PRINT*/));
		fflush($handle);
		flock($handle, LOCK_UN);
		fclose($handle);
	}

	public function getMulti($namespace, $keys, $serverKey = false) {
		$result = [];

		foreach ($keys as $key) {
			$result[$key] = $this->get($namespace, $key);
		}

		return $result;
	}

	public function setMulti($namespace, $items, $expire = 0, $serverKey = false) {
		$namespace = $this->validateNamespace($namespace);

		foreach ($items as $key => $value) {
			$this->set($namespace, $key, $value);
		}
	}

	public function purge($namespace = '') {
		$this->delete($namespace);
	}

	public function delete($namespace, $key = false) {
		$namespace = $this->validateNamespace($namespace);

		$name = $this->cacheDir . $namespace;

		if ($key) {
			$name .= 'cache.' . basename($key) . '.*';
		} else {
			$name .= '*';
		}

		$files = glob($name);

		if ($files) {
			foreach ($files as $file) {
				if ($file[0] === '.') {
					continue;
				}

				if (! @unlink($file)) {
					clearstatcache(false, $file);
				}
			}
		}
	}
}
