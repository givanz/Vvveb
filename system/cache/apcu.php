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
	private $expire = 0;

	private $options = [];

	private $active;

	private $cachePrefix = 'cache.';

	public function __construct($options) {
		$this->options += $options;

		$this->cachePrefix = md5(DIR_ROOT); //unique instance for shared hosting
		$this->expire      = $this->options['expire'] ?? $this->expire;
		$this->active      = function_exists('apcu_cache_info') && ini_get('apc.enabled');
	}

	private function key($namespace, $key = '') {
		return str_replace(['\\', '/'], '.', $this->cachePrefix . $namespace . $key);
	}

	public function get($namespace, $key) {
		return $this->active ? apcu_fetch($this->key($namespace, $key)) : null;
	}

	public function set($namespace, $key, $value, $expire = null) {
		if (! $expire) {
			$expire = $this->expire;
		}

		if ($this->active) {
			apcu_store($this->key($namespace, $key), $value, $expire);
		}
	}

	public function getMulti($namespace, $keys, $serverKey = false) {
		$result   = [];
		$fullKeys = [];

		foreach ($keys as &$key) {
			// simulate with single call version
			//$result[$key] = $this->get($namespace, $key);

			//add namespace
			$newKey         = $this->key($namespace, $key);
			$fullKeys[$key] = $newKey;
			$key            = $newKey;
		}

		$result = $this->active ? apcu_fetch($keys) : null;

		if ($result) {
			foreach ($fullKeys as $key => &$fullKey) {
				$fullKeys[$key] = $result[$fullKey] ?? null;
			}

			return $fullKeys;
		}

		return $result;
	}

	public function setMulti($namespace, $items, $expire = null, $serverKey = false) {
		foreach ($items as $key => $value) {
			$this->set($namespace, $key, $value, $expire);
		}
	}

	public function delete($namespace, $key = '') {
		if ($this->active) {
			if ($namespace) {
				if ($key) {
					return apcu_delete($this->key($namespace, $key));
				} else {
					return apcu_delete(new \APCUIterator('/' . $this->key($namespace, $key) . '.*/'));
					$cache_info = apcu_cache_info();

					$cache_list = $cache_info['cache_list'];

					foreach ($cache_list as $entry) {
						if (strpos($entry['info'], $this->key($namespace, $key)) === 0) {
							apcu_delete($entry['info']);
						}
					}
				}
			} else {
				return apcu_clear_cache();
			}
		}
	}

	public function purge($namespace = '') {
		$status = false;

		if (function_exists('apcu_clear_cache')) {
			$status = apcu_clear_cache();
		}

		return $status;
	}
}
