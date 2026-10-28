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
	public const string stringRegex = '{(\w+)}';

	public const string varRegex = '[{#]([a-zA-Z]\w+)({([\d,]+)})?[#}]';

	public const string stringLimitRegex = '{([a-zA-Z]\w+){([\d,]+)}}';

	public const string numericRegex = '#([a-zA-Z]\w+)#';

	public const string numericLimitRegex = '#([a-zA-Z]\w+){([\d,]+)}#';

	public const string wildcardRegex = '\*';

	/** @var array<string, array<string, string>> */
	private static array $routes = [];

	/** @var array<string, array{0: string, 1: string}> */
	private static array $urls = [];

	/** @var array<string, array<int, array{url: string, parameters: list<string>, count: int}> >|null */
	private static ?array $modules = null;

	private static bool $init = false;

	/**
	 * Process a route pattern into regex and store module data.
	 *
	 * @param string                $url  The route URL pattern.
	 * @param array<string, string> $data Route configuration data.
	 *
	 * @return string The compiled regex pattern.
	 */
	private static function processRoute(string $url, array $data): string {
		$module = $data['module'];
		unset($data['edit'], $data['module']);

		$parameters = [];

		if (preg_match_all('/' . self :: varRegex . '/', $url, $matches)) {
			$parameters = $matches[1];
		}

		if ($data) {
			$parameters = array_merge($parameters, array_keys($data));
		}

		self :: $modules[$module][] = ['url' => $url, 'parameters' => $parameters, 'count' => count($parameters)];
		uasort(self :: $modules[$module], fn (array $a, array $b): int => $b['count'] <=> $a['count']);

		$route = $url;
		$url   = str_replace('/', '\/', $url);
		$url   = preg_replace('/' . self :: numericLimitRegex . '/', '(?<$1>\d{$2})', $url);
		$url   = preg_replace('/' . self :: numericRegex . '/', '(?<$1>\d+)', $url);
		$url   = preg_replace('/' . self :: stringLimitRegex . '/', '(?<$1>[^$\/]{$2})', $url);
		$url   = preg_replace('/' . self :: stringRegex . '/', '(?<$1>[^$\/]+)', $url);
		$url   = preg_replace('/' . self :: wildcardRegex . '/', '.*?', $url);

		self :: $urls[$route] = [$url, $module];

		return $url;
	}

	/**
	 * Add a route with its URL pattern and configuration data.
	 *
	 * @param string                $url  The route URL pattern.
	 * @param array<string, string> $data Route configuration data.
	 */
	public static function addRoute(string $url, array $data): void {
		self :: $routes[$url] = $data;
		self :: processRoute($url, $data);
	}

	/**
	 * Remove one or more routes by URL pattern.
	 *
	 * @param string|array<int, string> $url A single URL pattern or array of URL patterns to remove.
	 */
	public static function removeRoute(string|array $url): void {
		if (is_array($url)) {
			foreach ($url as $route) {
				$entry = self :: $urls[$route] ?? false;

				if ($entry) {
					unset(self :: $modules[$entry[1]]);
				}
				unset(self :: $urls[$route]);
			}
		} else {
			$entry = self :: $urls[$url] ?? false;

			if ($entry) {
				unset(self :: $modules[$entry[1]]);
			}
			unset(self :: $urls[$url]);
		}
	}

	/**
	 * Initialize routes from cache or config files.
	 *
	 * @param string   $app     Application name.
	 * @param int|null $site_id Optional site ID for site-specific routes.
	 *
	 * @return bool Always returns true.
	 */
	public static function init(string $app = 'app', ?int $site_id = null): bool {
		$cacheDriver = Cache :: getInstance();
		if ($site_id) {
			$cacheKey = $site_id;
		} else {
			$cacheKey = $app;
		}

		if ($result = $cacheDriver->get('routes', $cacheKey)) {
			self::$routes  = $result['routes'];
			self::$urls    = $result['urls'];
			self::$modules = $result['modules'];
		} else {
			$routesConfig = DIR_ROOT . '/config/';
			if ($site_id) {
				$routesConfig .= "site-$site_id-routes.php";
			} else {
				$routesConfig .= "$app-routes.php";
			}

			if (file_exists($routesConfig)) {
				self :: $routes += include $routesConfig;
			}
			[self :: $routes] = Event::trigger(__CLASS__, __FUNCTION__, self :: $routes);

			foreach (self :: $routes as $url => $data) {
				self :: processRoute($url, $data);
			}

			$result            = [];
			$result['routes']  = self::$routes;
			$result['modules'] = self::$modules;
			$result['urls']    = self::$urls;

			$cacheDriver->set('routes', $cacheKey, $result);
		}

		self :: $init = true;

		return true;
	}

	/**
	 * Match a URL against registered route patterns and return extracted parameters.
	 *
	 * @param string $url The URL to match.
	 *
	 * @return array<string, string>|false Matched parameters or false.
	 */
	public static function match(string $url): array|false {
		if (!self :: $init) {
			self :: init();
		}
		$url = preg_replace('/\?.*$/', '', str_replace('//', '/', $url));

		foreach (self :: $urls as $route => $data) {
			$matches                = [];
			$params                 = self::$routes[$route] ?? '';
			[$pattern, $module]     = $data;

			if ($url == $pattern || preg_match('/^' . $pattern . '$/', $url, $matches)) {
				$parameters = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);

				$parameters['route']   = $route;
				$parameters['module']  = $module;
				$parameters['pattern'] = $pattern;

				$parameters += $params;

				return $parameters;
			}
		}

		return false;
	}

	/**
	 * Get the configuration data for a route by its pattern key.
	 *
	 * @param string $route The route pattern key.
	 *
	 * @return array{module: string, edit?: string} Route data or empty array.
	 */
	public static function get(string $route): array {
		return self :: $routes[$route] ?? [];
	}

	/**
	 * Get all registered routes.
	 *
	 * @return array<string, array<string, string>> All routes.
	 */
	public static function getRoutes(): array {
		return self :: $routes;
	}

	/**
	 * Replace variable placeholders in a URL with parameter values.
	 *
	 * @param string                $url        URL pattern with placeholders.
	 * @param array<string, string> $parameters Key-value pairs for replacement.
	 *
	 * @return string The URL with placeholders replaced.
	 */
	public static function varReplace(string $url, array $parameters): string {
		return preg_replace_callback(
			'/' . self :: stringRegex . '|' . self :: numericRegex . '/',
			function(array $matches) use ($parameters): string {
				$var = $matches[1];

				if (isset($parameters[$var])) {
					return $parameters[$var];
				}

				return '';
			},
			$url
		);
	}

	/**
	 * Get route data for a module name.
	 *
	 * @param string $module The module name.
	 *
	 * @return array<int, array{url: string, parameters: list<string>, count: int}> Route data for the module.
	 */
	public static function getRouteData(string $module): array {
		if (!self :: $init) {
			self :: init();
		}

		return self :: $modules[$module] ?? [];
	}

	/**
	 * Get URL data by matching against registered routes.
	 *
	 * @param string|false $url The URL to resolve, or false to use current URL.
	 *
	 * @return array<array-key, mixed>|false Matched route parameters with route metadata, or false on no match.
	 */
	public static function getUrlData(string|false $url = false): array|false {
		if (!$url) {
			$url = \Vvveb\getCurrentUrl();
		}

		$parameters = self :: match($url);
		$route      = stripslashes($parameters['route'] ?? '');

		if ($parameters) {
			$parameters += self :: $routes[$route];

			if (isset($parameters['edit'])) {
				$parameters['edit'] = self :: varReplace($parameters['edit'], $parameters);
			}
		}

		return $parameters;
	}

	/**
	 * Generate a URL from a module route name and parameters.
	 *
	 * @param string                      $route      The module/route name.
	 * @param array<string, string>|false $parameters Query parameters or false for no parameters.
	 *
	 * @return string|null The resolved URL, or null if the route is not found.
	 */
	public static function url(string $route, array|false $parameters = false): ?string {
		if (!self :: $init) {
			self :: init();
		}

		if (isset(self :: $modules[$route])) {
			$pattern    = self :: $modules[$route][0]['url'] ?? '';

			if ($parameters && isset($parameters['route'])) {
				unset($parameters['route']);
			}

			$parameters_count = is_array($parameters) ? count($parameters) : 0;
			$param_keys       = is_array($parameters) ? array_keys($parameters) : [];
			$no_parameters    = $pattern;

			foreach (self :: $modules[$route] as $value) {
				if ($value['parameters'] && $parameters_count) {
					if (count(array_intersect($value['parameters'], $param_keys)) === count($value['parameters'])) {
						$pattern  = $value['url'];

						break;
					}
				} else {
					$no_parameters = $value['url'];
				}
			}

			if (!$parameters) {
				$pattern = $no_parameters;
			}

			$missing = false;
			$url     = preg_replace_callback(
				'/' . self :: varRegex . '/',
				function(array $matches) use ($parameters, &$missing): string {
					$var = $matches[1];

					if (isset($parameters[$var])) {
						return $parameters[$var];
					} else {
						$missing = true;
					}

					return '';
				},
				$pattern
			);

			if ($missing) {
				return '/?route=' . $route . '&' . (is_array($parameters) ? http_build_query($parameters) : '');
			} else {
				return $url;
			}
		}

		return null;
	}
}
