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
	private $expire;

	private $redis;

	private $cachePrefix = ''; //'cache.';

	private $options = ['expire' => 3000, 'prefix' => 'vvveb.'];

	public function __construct($options) {
		$this->options += $options;

		$this->expire      = $this->options['expire'] ?? $this->expire;
		$this->cachePrefix = $this->options['prefix'] ?? $this->cachePrefix;

		$this->redis = new \Redis();
		$this->redis->pconnect($this->options['host'], $this->options['port']);

		if (isset($this->options['password'])) {
			$this->redis->auth($this->options['password']);
		}
	}

	private function key($namespace, $key = '') {
		return $this->cachePrefix . ($namespace ? ".$namespace" : '') . $key;
	}

	public function get($namespace, $key) {
		$data = $this->redis->get($this->key($namespace, $key));

		return json_decode($data, true);
	}

	public function set($namespace, $key, $value, $expire = null) {
		$expire = $expire ?? $this->expire;
		$_key   = $this->key($namespace, $key);
		$status = $this->redis->set($_key, json_encode($value));

		if ($status && $expire) {
			$this->redis->expire($_key, $expire);
		}
	}

	public function getMulti($namespace, $keys, $serverKey = false) {
		$result = [];

		foreach ($keys as $key) {
			$result[$key] = $this->get($namespace, $key);
		}

		return $result;
	}

	public function setMulti($namespace, $items, $expire = null, $serverKey = false) {
		$expire = $expire ?? $this->expire;

		foreach ($items as $key => $value) {
			$this->set($namespace, $key, $value, $expire);
		}
	}

	public function delete($namespace, $key) {
		if ($key) {
			$keys = $this->key($namespace, $key);
		} else {
			if ($namespace) {
				$keys = $this->key($namespace, '*');
			} else {
				$keys = $this->key('*');
			}
		}

		$this->redis->del($keys);

		return true;
	}
}
