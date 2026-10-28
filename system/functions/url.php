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

namespace Vvveb;

/**
 * Resolve a redirect Location against the current URL (RFC 3986):
 * absolute urls pass through, root-relative resolve against the origin,
 * relative resolve against the containing directory. Pure, tested.
 *
 * @param string $base     The current URL.
 * @param string $location The redirect Location value.
 *
 * @return string The resolved absolute URL, or '' when unresolvable.
 */
function resolveUrl(string $base, string $location): string {
	$location = trim($location);

	if (preg_match('#^https?://#i', $location)) {
		return $location;
	}

	$parts = parse_url($base);

	if (!is_array($parts) || ($parts['host'] ?? '') === '') {
		return '';
	}

	$scheme = strtolower($parts['scheme'] ?? 'https');
	$prefix = $scheme . '://' . $parts['host'] . (isset($parts['port']) ? ':' . $parts['port'] : '');

	if (str_starts_with($location, '/')) {
		return $prefix . $location;
	}

	$path = (string)($parts['path'] ?? '');

	if (!str_ends_with($path, '/')) {
		$path = (string)preg_replace('#/[^/]*$#', '', rtrim($path, '/'));
	}

	return $prefix . rtrim($path, '/') . '/' . ltrim($location, '/');
}

/**
 * Generate a URL from a route name or array of parameters.
 *
 * @param string|array<string,mixed> $parameters      Route name or URL parameters
 * @param array<string,mixed>|bool   $mergeParameters Parameters to merge with existing URL parameters
 * @param bool                       $useCurrentUrl   Whether to use the current URL as base
 *
 * @return string The generated URL
 */
function url(string|array $parameters, array|bool $mergeParameters = false, bool $useCurrentUrl = true): string {
	if (is_string($parameters) && $parameters) {
		$result = '';

		if (isset($mergeParameters['scheme'])) {
			$result .= $mergeParameters['scheme'] . ':';
			unset($mergeParameters['scheme']);
		}

		if (isset($mergeParameters['host'])) {
			$result .= '//' . \Vvveb\System\Sites::url($mergeParameters['host']);
			unset($mergeParameters['host']);

			if (isset($mergeParameters['scheme'])) {
				$result = $mergeParameters['scheme'] . ":$result";
				unset($mergeParameters['scheme']);
			}
		}

		$path = $mergeParameters['path'] ?? SITE_PATH;

		$url = System\Routes::url($parameters, $mergeParameters);
		$result .= V_SUBDIR_INSTALL . $path . ($url ?? '');
	} else {
		static $url       = '';
		static $urlParams = [];

		if ($useCurrentUrl && !$url) {
			$url = parse_url($_SERVER['REQUEST_URI'] ?? '');

			if (isset($url['query'])) {
				parse_str($url['query'], $urlParams);
			}
		}

		if (!is_array($parameters)) {
			$parameters = [];
		}

		if ($mergeParameters) {
			if (is_array($mergeParameters)) {
				$mergeParameters = array_merge($urlParams, $mergeParameters);
			} else {
				$mergeParameters = $urlParams;
			}

			if (is_array($mergeParameters)) {
				$parameters = array_merge($mergeParameters, $parameters);
			}
		}

		$result = '';
		$path   = $parameters['path'] ?? SITE_PATH;

		if (isset($parameters['host'])) {
			$result .= '//' . \Vvveb\System\Sites::url($parameters['host']);
			unset($parameters['host']);

			if (isset($parameters['scheme'])) {
				$result = $parameters['scheme'] . ":$result";
				unset($parameters['scheme']);
			}
		} else {
			if (!$useCurrentUrl) {
				$result .= V_SUBDIR_INSTALL . $path;
			}
		}

		$result .= ($useCurrentUrl ? $url['path'] ?? '' : '') . ($parameters ? '?' . urldecode(http_build_query($parameters)) : '');
	}

	return $result;
}

/**
 * Get the current template name.
 *
 * @return string|false|null Template name, or false/null if not set
 */
function getCurrentTemplate(): string|false|null {
	return System\Core\View :: getInstance()->template();
}

/**
 * Get route data for a given URL.
 *
 * @param string $url The URL to look up
 *
 * @return array<array-key, mixed>|false Route data array, or false if not found
 */
function getUrlRoute(string $url): array|false {
	return \Vvveb\System\Routes::getUrlData($url);
}

/**
 * Get the current request URI.
 *
 * @return string Current request URI
 */
function getCurrentUrl(): string {
	return $_SERVER['REQUEST_URI'] ?? '';
}

/**
 * Get the admin path URL.
 *
 * @return string Admin path with leading and trailing slashes
 */
function adminPath(): string {
	return V_SUBDIR_INSTALL . '/' . \Vvveb\config('admin.path', 'admin') . '/';
}

/**
 * Get the public URL path.
 *
 * @return string Public URL path
 */
function publicUrlPath(): string {
	$public_path = PUBLIC_PATH;

	if (V_SUBDIR_INSTALL) {
		$public_path = str_replace(V_SUBDIR_INSTALL, '', $public_path);
	}

	if ($public_path == '/public/' || $public_path == '/public/admin/') {
		return PUBLIC_PATH;
	} else {
		return '/';
	}
}

/**
 * Get the current theme URL path.
 *
 * @return string Theme URL path
 */
function themeUrlPath(): string {
	$theme = \Vvveb\System\Core\View::getInstance()->getTheme();
	if (APP == 'app') {
		$path = PUBLIC_THEME_PATH . 'themes/' . $theme . '/';
	} else {
		$path = PUBLIC_THEME_PATH . $theme . '/';
	}

	return $path;
}

/**
 * Return current module name.
 *
 * @return string
 */
function getModuleName(): string {
	return strtolower(\Vvveb\System\Core\FrontController::getModuleName());
}

/**
 * Return current action name.
 *
 * @return string
 */
function getActionName(): string {
	return strtolower(\Vvveb\System\Core\FrontController::getActionName());
}

/**
 * Check if the page is loaded in the editor.
 *
 * @return bool
 */
function isEditor(): bool {
	return isset($_GET['r']);
}

/**
 * Checks if the current user is an admin.
 *
 * @return bool True if the current user is an admin, false otherwise.
 */
function isAdmin(): bool {
	return (bool)System\User\Admin::current();
}

/**
 * Get a request parameter value.
 *
 * @param string $key The request parameter key.
 *
 * @return mixed The parameter value, or false if not set.
 */
function get(string $key): mixed {
	return System\Core\Request::getInstance()->get[$key] ?? false;
}

/**
 * Downloads content from a URL using cURL or file_get_contents.
 *
 * @param string $url The URL to download.
 *
 * @return string|null The downloaded content, or null on failure.
 */
function download(string $url): ?string {
	$result = null;

	$url = html_entity_decode($url);
	$ips = validateUrl($url);

	if (!$ips) {
		return null;
	}

	if (function_exists('curl_init')) {
		$ch = curl_init($url);

		if ($ch) {
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_RESOLVE, $ips);
			curl_setopt($ch, CURLOPT_FAILONERROR, true);
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
			curl_setopt($ch, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT'] ?? 'Vvveb ' . V_VERSION);
			$result   = curl_exec($ch);
			$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

			if ($httpcode != 200) {
				return null;
			}
		}
	} else {
		if (ini_get('allow_url_fopen') == '1') {
			$context_options = [
				'http' => [
					'timeout'       => 5,
					'ignore_errors' => 1,
				],
			];
			$context  = stream_context_create($context_options);
			$result   = @file_get_contents($url, false, $context) ?: null;
		}
	}

	return $result;
}

/**
 * Fetches URL content with caching support.
 *
 * @param string $url       The URL to fetch.
 * @param bool   $cache     Whether to cache the result (default true).
 * @param int    $expire    Cache expiration in seconds (default 604800).
 * @param int    $timeout   Request timeout in seconds (default 5).
 * @param bool   $exception Whether to throw exceptions on failure (default true).
 *
 * @return mixed The fetched content, or null on failure.
 */
function getUrl(string $url, bool $cache = true, int $expire = 604800, int $timeout = 5, bool $exception = true): mixed {
	$cacheDriver  = System\Cache :: getInstance();
	$cacheKey     = md5($url);
	$result       = false;

	$ips = validateUrl($url);

	if (!$ips) {
		return null;
	}

	if ($cache && ($result = $cacheDriver->get('url', $cacheKey))) {
		return $result;
	} else {
		$result = false;
		$url    = html_entity_decode($url);
		//try with curl
		if (function_exists('curl_init') && ($ch = curl_init($url))) {
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_RESOLVE, $ips);
			curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
			curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
			curl_setopt($ch, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT'] ?? 'Vvveb ' . V_VERSION);

			if (DEBUG) {
				curl_setopt($ch, CURLOPT_VERBOSE, true);
				$streamVerboseHandle = fopen('php://temp', 'w+');
				curl_setopt($ch, CURLOPT_STDERR, $streamVerboseHandle);
			}

			$result = curl_exec($ch);

			if ($result) {
				$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
				if ($httpcode != 200) {
					return null;
				}

				if ($cache) {
					$cacheDriver->set('url', $cacheKey, $result);
				}

				return $result;
			} else {
				if ($exception) {
					$message = 'Curl error: ' . curl_errno($ch) . ' - ' . curl_error($ch);

					if (DEBUG) {
						$message .= "\n" . print_r(curl_getinfo($ch), true);
						rewind($streamVerboseHandle);
						$message .= "\n" . stream_get_contents($streamVerboseHandle);
					}

					throw new \Exception($message);
				}
			}
		} else {
			//try with file get contents
			if (ini_get('allow_url_fopen') == '1') {
				$context_options = [
					'http' => [
						'timeout'       => $timeout,
						'ignore_errors' => 1,
					],
				];

				$context         = stream_context_create($context_options);

				if ($exception) {
					$result = file_get_contents($url, false, $context);
				} else {
					$result = @file_get_contents($url, false, $context);
				}
			}

			if ($result) {
				if ($cache) {
					$cacheDriver->set('url', $cacheKey, $result);
				}

				return $result;
			}
		}
	}

	return $result;
}
