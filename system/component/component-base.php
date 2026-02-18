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

use function Vvveb\session as sess;
use function Vvveb\siteSettings;
use Vvveb\System\Core\Request;
use Vvveb\System\Core\View;
use Vvveb\System\Event;
use Vvveb\System\Session;
use Vvveb\System\User\User;

#[\AllowDynamicProperties]
class ComponentBase {
	public $cacheKey;

	public $cacheExpire = 3600; //seconds

	public static $global;

	protected $options;

	protected $_hash;

	public static $defaultOptions = [];

	function __construct($options = []) {
		$request = Request :: getInstance();

		if (! self :: $global) {
			$user                                  = User::current();
			$site                                  = siteSettings();
			self :: $global['start']               = 0;
			self :: $global['site_id']             = (APP == 'admin') ? sess('site_id') ?? SITE_ID : (defined('SITE_ID') ? SITE_ID : 0);
			//self :: $global['site']                = siteSettings();
			self :: $global['user_id']             = $user['user_id'] ?? null;
			self :: $global['user_group_id']       = $user['user_group_id'] ?? 1;
			self :: $global['languages']           = $site['languages'] ?? [];
			self :: $global['default_language']    = sess('default_language') ?? $site['language'] ?? 'en';
			self :: $global['default_language_id'] = sess('default_language_id') ?? $site['language_id'] ?? 1;
			self :: $global['currency_id']         = sess('currency_id') ?? $site['currency_id'] ?? 1;
			self :: $global['currency']            = sess('currency') ?? $site['currency'] ?? 'usd';
			self :: $global['currencies']          = $site['currencies'] ?? [];
			self :: $global['language']            = ($site['language'] ?? '') ?: 'en';
			self :: $global['language_id']         = ($site['language_id'] ?? '') ?: 1;

			if ($language = sess('language')) {
				self :: $global['language'] = $language;
			}

			if ($language_id = sess('language_id')) {
				self :: $global['language_id'] = $language_id;
			}

			if (isset($request->request['language']) && is_string($request->request['language'])) {
				self :: $global['language'] = $request->request['language'];
			}

			if (isset($request->request['language_id']) && is_string($request->request['language_id'])) {
				self :: $global['language_id'] = $request->request['language_id'];
			}
		}

		static :: $defaultOptions += self :: $global;

		foreach (['site_id', 'language_id', 'currency_id', 'user_id', 'user_group_id'] as $key) {
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

				$value = ($request->request[$key] ?? ($request->get[$key] ?? null));
			}

			if ($value === null) {
				unset($this->options[$key]);
			}
		}

		list($this->options) = Event :: trigger(get_class($this), __FUNCTION__, $this->options);

		$this->di($this);
	}

	static function di(&$component) {
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

		$className      = strtolower(str_replace(['Vvveb\Plugins\\', 'Vvveb\Component\\', '\Component\\'], '',get_class($this)));
		$this->cacheKey = str_replace('\\','-', $className) . '.' . md5(serialize($this->options));

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
			return $options + static :: $defaultOptions;
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
