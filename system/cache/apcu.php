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
	private $expire;

	private $active;

	private $cachePrefix = 'cache.';

	public function __construct($expire = 3600) {
		$this->expire = $expire;
		$this->active = function_exists('apcu_cache_info') && ini_get('apc.enabled');
	}

	public function get($namespace, $key) {
		return $this->active ? apcu_fetch($this->cachePrefix . $namespace . $key) : [];
	}

	public function set($namespace, $key, $value, $expire = 0) {
		if (! $expire) {
			$expire = $this->expire;
		}

		if ($this->active) {
			apcu_store($this->cachePrefix . $namespace . $key, $value, $expire);
		}
	}

	public function getMulti($namespace, $keys, $serverKey = false) {
		$result = [];

		foreach ($keys as $key) {
			$result[$key] = $this->get($namespace, $key);
		}

		return $result;
	}

	public function setMulti($namespace, $items, $expire = 0, $serverKey = false) {
		foreach ($items as $key => $value) {
			$this->set($namespace, $key, $value);
		}
	}

	public function delete($namespace, $key) {
		if ($this->active) {
			$cache_info = apcu_cache_info();

			$cache_list = $cache_info['cache_list'];

			foreach ($cache_list as $entry) {
				if (strpos($entry['info'], $this->cachePrefix . $namespace . $key) === 0) {
					apcu_delete($entry['info']);
				}
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
