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

namespace Vvveb\System;

class Routes {
	const stringRegex = '{(\w+)}';

	const varRegex = '[{#]([a-zA-Z]\w+)({([\d,]+)})?[#}]';

	const stringLimitRegex = '{([a-zA-Z]\w+){([\d,]+)}}';

	const numericRegex = '#([a-zA-Z]\w+)#';

	const numericLimitRegex = '#([a-zA-Z]\w+){([\d,]+)}#';

	const wildcardRegex = '\*';

	private static $routes = [];

	private static $urls = null;

	private static $modules = null;

	private static function processRoute($url, $data) {
		$module = $data['module'];
		//self :: $modules[$module] = $url;

		$parameters = [];

		if (preg_match_all('/' . self :: varRegex . '/', $url, $matches)) {
			if ($matches[1]) {
				$parameters = $matches[1];
			}
		}

		self :: $modules[$module][] = ['url' => $url, 'parameters' => $parameters, 'count' => count($parameters)];
		//add urls with most parameters first for proper matching
		uasort(self :: $modules[$module], function ($a, $b) {
			return $b['count'] <=> $a['count'];
		});

		//escape / for regex
		$url = str_replace('/', '\/', $url);
		//numeric limit
		$url = preg_replace('/' . self :: numericLimitRegex . '/', '(?<$1>\d{$2})', $url);
		//numeric
		$url = preg_replace('/' . self :: numericRegex . '/', '(?<$1>\d+)', $url);
		//string limit
		$url = preg_replace('/' . self :: stringLimitRegex . '/', '(?<$1>[^$\/]{$2})', $url);
		//string
		$url = preg_replace('/' . self :: stringRegex . '/', '(?<$1>[^$\/]+)', $url);
		//wildcard
		$url = preg_replace('/' . self :: wildcardRegex . '/', '.*?', $url);

		self :: $urls[$url] = $module;
	}

	public static function addRoute($url, $data) {
		self :: $routes[$url] = $data;
		self :: processRoute($url, $data);
	}

	public static function removeRoute($url) {
		if (is_array($url)) {
			foreach ($url as $route) {
				$module = self :: $urls[$route] ?? false;

				if ($module) {
					unset(self :: $modules[$module]);
				}
				unset(self :: $urls[$route]);
			}
		} else {
			$module = self :: $urls[$url] ?? false;

			if ($module) {
				unset(self :: $modules[$module]);
			}
			unset(self :: $urls[$url]);
		}
	}

	public static function init() {
		self :: $routes += include DIR_ROOT . '/config/routes.php';
		list(self :: $routes) = Event::trigger(__CLASS__, __FUNCTION__ , self :: $routes);

		foreach (self :: $routes as $url => $data) {
			self :: processRoute($url, $data);
		}

		return true;
	}

	public static function match($url) {
		if (! self :: $routes) {
			self :: init();
		}

		//remove get parameters
		$url = preg_replace('/\?.+$/', '', $url);

		foreach (self :: $urls as $pattern => $route) {
			if ($url == $pattern || preg_match('/^' . $pattern . '$/', $url, $matches)) {
				$parameters = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);

				$parameters['route'] = $route;

				return $parameters;
			}
		}

		return false;
	}

	public static function get($route) {
		return self :: $routes[$route] ?? [];
	}

	public static function varReplace($url, $parameters) {
		return preg_replace_callback('/' . self :: stringRegex . '|' . self :: numericRegex . '/',
			function ($matches) use ($parameters) {
				$var = $matches[1];

				if (isset($parameters[$var])) {
					return $parameters[$var];
				}

				return '';
			}, $url);
	}

	public static function getRouteData($module) {
		if (! self :: $routes) {
			self :: init();
		}

		return self :: $modules[$module] ?? [];
	}

	public static function getUrlData($url = false) {
		if (! $url) {
			$url = \Vvveb\getCurrentUrl();
		}

		$parameters = self :: match($url);

		if ($parameters) {
			$parameters['pattern'] = self :: $modules[$parameters['route']][0]['url'];
			$parameters            = $parameters + self :: $routes[$parameters['pattern']];

			if (isset($parameters['edit'])) {
				$parameters['edit'] = self :: varReplace($parameters['edit'], $parameters);
			}
		}

		return $parameters;
	}

	public static function url($route, $parameters = false) {
		if (! self :: $routes) {
			self :: init();
		}

		if (isset(self :: $modules[$route])) {
			$pattern    = self :: $modules[$route][0]['url'] ?? '';

			if ($parameters && isset($parameters['route'])) {
				unset($parameters['route']);
			}

			$parameters_count = is_array($parameters) ? count($parameters) : 0;
			$param_keys       = is_array($parameters) ? array_keys($parameters) : [];

			//if ($param_keys) {
			foreach (self :: $modules[$route] as $value) {
				//select route that has all parameters
				if ($value['parameters'] && $parameters_count) {
					if (count(array_intersect($value['parameters'], $param_keys)) === count($value['parameters'])) {
						$pattern  = $value['url'];

						break;
					}
				} else {
					$no_parameters = $value['url'];
				}
			}
			//}

			if (! $parameters) {
				$pattern = $no_parameters;
			}

			$missing = false;
			$url     = preg_replace_callback('/' . self :: varRegex . '/',
				function ($matches) use ($parameters, &$missing) {
					$var = $matches[1];

					if (isset($parameters[$var])) {
						return $parameters[$var];
					} else {
						$missing = true;
					}

					return '';
				}, $pattern);

			if ($missing) {
				return (V_SUBDIR_INSTALL ? V_SUBDIR_INSTALL : '') . '/?route=' . $route . '&' . (is_array($parameters) ? http_build_query($parameters) : '');
			} else {
				return (V_SUBDIR_INSTALL ? V_SUBDIR_INSTALL : '') . $url;
			}
		}
	}
}
