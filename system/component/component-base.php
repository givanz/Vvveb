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

namespace Vvveb\System\Component;

use function Vvveb\session;
use Vvveb\System\Core\Request;
use Vvveb\System\User\User;

#[\AllowDynamicProperties]
class ComponentBase {
	public $cacheKey;

	public $cacheExpire = 3600; //seconds

	public static $global;

	function __construct($options = []) {
		$request = Request :: getInstance();

		if (! self :: $global) {
			$user                                  = User::current();
			self :: $global['start']               = 0;
			self :: $global['site_id']             = defined('SITE_ID') ? SITE_ID : 0;
			self :: $global['user_id']             = $user['user_id'] ?? null;
			self :: $global['language_id']         = session('language_id') ?? 1;
			self :: $global['language']            = session('language') ?? 'en_US';
			self :: $global['default_language']    = session('default_language') ?? 'en_US';
			self :: $global['default_language_id'] = session('default_language_id') ?? 1;
			self :: $global['currency_id']         = session('currency_id') ?? 1;
		}

		static :: $defaultOptions = array_merge(self :: $global, static :: $defaultOptions);

		foreach (['site_id', 'language_id', 'currency_id'] as $key) {
			if (! isset(static :: $defaultOptions[$key]) || empty(static :: $defaultOptions[$key])) {
				static :: $defaultOptions[$key] = self :: $global[$key];
			}
		}

		if (isset(static :: $defaultOptions)) {
			$this->options = $this->filter($options);
		}

		foreach ($this->options as $key => &$value) {
			if (is_array($value)) {
				foreach ($value as $val) {
					if (strpos($val, 'url') === 0) {
						//check if url parameter is specified ex: url.slug
						if ($dot = strrpos($val,'.')) {
							$key = substr($val, $dot + 1);
						}

						if (isset($request->request[$key])) {
							$value = $request->request[$key];

							break;
						} else {
							if (isset($value[1])) {
								$value = $value[1];
							} else {
								$value = null;
							}
						}
					}
				}
			}

			if ($value && is_string($value) && strpos($value, 'url') === 0) {
				//check if url parameter is specified ex: url.slug
				if ($dot = strrpos($value,'.')) {
					$key = substr($value, $dot + 1);
				}

				$value = isset($request->request[$key]) ? $request->request[$key] : null;
			}
		}

		$this->di($this);
	}

	static function di(&$component) {
		return;
		$component->request = Request::getInstance();
		$component->view    = View::getInstance();
		$component->session = Session::getInstance();
	}

	function invalidateCache() {
	}

	function cacheKey() {
		if (isset($this->cacheKey)) {
			return $this->cacheKey;
		}

		$className      = strtolower(str_replace('Vvveb\\Component\\', '',get_class($this)));
		$this->cacheKey = md5($className . serialize($this->options));

		return $this->cacheKey;
	}

	function filter(&$options) {
		//remove fields not declared in the class
		if (is_array($options)) {
			if (isset($options['_hash'])) {
				$this->_hash = $options['_hash'];
				unset($options['_hash']);
			}

			//$intersect = array_intersect_key($options, static :: $defaultOptions);
			//$diff = array_diff_key(static :: $defaultOptions, $options);
			//return $options = $intersect + $diff;
			return array_merge(static :: $defaultOptions, $options);
		} else {
			return static :: $defaultOptions;
		}

		return $options;
	}

	function results() {
		//check cache
		$cache = Cache :: getInstance();

		return $cache->get($this->cacheKey);
	}

	function generateCache($results) {
		$cache  = Cache :: getInstance();
		$expire = $_SERVER['REQUEST_TIME'] + $this->cacheExpire;

		if (! $results) {
			$results = 0;
		}
		$cache->set($this->cacheKey, $results, $expire + COMPONENT_CACHE_EXPIRE);

		return $cache->set('expire_' . $this->cacheKey, $expire, $expire + COMPONENT_CACHE_EXPIRE);
	}
}
