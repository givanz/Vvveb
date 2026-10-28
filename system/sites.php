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

use function Vvveb\__;

class Sites {
	/** @var array<string, array<string, mixed>>|null All sites. */
	private static ?array $sites = null;

	/** @var array<string, array<string, string>> Cached host regex matches keyed by host. */
	private static array $host_matches = [];

	public const HOST_REGEX = '@(?<prefix>https://|http://|//|^)(?<subdomain>.*?)?\.?(?<domain>[^\.]+)\.(?<tld>[^\.]+|[^\.]{2,3}\.[^\.]{2,3})((?<path>/.*)|$)@';

	/** @var array<string, array{name: string, template: string, icon: string}> Site states */
	private static array $states = [];

	/**
	 * Get available site states with their templates and icons.
	 *
	 * @return array<string, array{name: string, template: string, icon: string}>
	 */
	public static function getStates(): array {
		self :: $states = [
			'live'        => ['name' => __('Live'), 	   'template' => 'index.html',			   'icon' => 'broadcast'],
			'maintenance' => ['name' => __('Maintenance'), 'template' => 'index.maintenance.html', 'icon' => 'tool'],
			'coming-soon' => ['name' => __('Coming soon'), 'template' => 'index.coming-soon.html', 'icon' => 'clock'],
		];

		return self :: $states;
	}

	/**
	 * Get the default site.
	 *
	 * @return array<string, mixed>|false The first site data.
	 */
	public static function getDefault(): array|false {
		$sites = self :: getSites();

		return current($sites);
	}

	/**
	 * Get all configured sites with resolved URLs.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	public static function getSites(): array {
		if (!self :: $sites) {
			self :: $sites = \Vvveb\config('sites');

			foreach (self::$sites as &$site) {
				$site['url'] = self :: url($site['host']) . V_SUBDIR_INSTALL . ($site['path'] ?? '' ? '/' . $site['path'] : '');
			}

			return self :: $sites;
		}

		return self :: $sites;
	}

	/**
	 * Get site data by site key.
	 *
	 * @param string $site_key Site host or key.
	 *
	 * @return array<string, mixed>|false Site data or false if not found.
	 */
	public static function getSiteByKey(string $site_key): array|false {
		$sites = self :: getSites();

		$site_key = self :: siteKey($site_key);

		if (isset($sites[$site_key])) {
			$site        = $sites[$site_key];
			$site['key'] = $site_key;

			return $site;
		}

		return false;
	}

	/**
	 * Get site data by site ID.
	 *
	 * @param int $site_id Site ID.
	 *
	 * @return array<string, mixed>|null Site data or null if not found.
	 */
	public static function getSiteById(int $site_id): ?array {
		$sites = self :: getSites();

		foreach ($sites as $site_key => $site) {
			if ($site['site_id'] == $site_id) {
				$site['key'] = $site_key;

				return $site;
			}
		}

		return null;
	}

	/**
	 * Get the theme for a site.
	 *
	 * @param string|int|false $site Site key or ID.
	 *
	 * @return string Theme name, defaults to 'default'.
	 */
	public static function getTheme(string|int|false $site = false): string {
		$data = self :: getSiteData($site);

		if ($data) {
			return $data['theme'];
		}

		return 'default';
	}

	/**
	 * Set the theme and optional template for a site.
	 *
	 * @param string|int $site     Site key or ID.
	 * @param string     $theme    Theme name.
	 * @param string     $template Template filename.
	 *
	 * @return mixed Result of setSiteData.
	 */
	public static function setTheme(string|int $site, string $theme, string $template = ''): mixed {
		if ($template) {
			self :: setSiteData($site, 'template', $template);
		}

		$result = self :: setSiteData($site, 'theme', $theme);

		try {
			Notifications::trigger('theme.activate', ['subject' => "Theme activated: $theme", 'message' => "Theme \"$theme\" was set as the active theme.", 'theme' => $theme]);
		} catch (\Throwable $e) {
			// notifications must never break site settings changes
		}

		return $result;
	}

	/**
	 * Split a URL into its components (prefix, subdomain, domain, tld, path).
	 *
	 * @param string|null $url URL to split, or null to use the request host.
	 *
	 * @return array<int|string, string> Associative array of URL components.
	 */
	public static function urlSplit(?string $url = null): array {
		$url ??= $_SERVER['HTTP_HOST'] ?? '';
		$host         = self :: getHost();
		$host_matches = self :: $host_matches[$host] ?? [];

		if (preg_match(self :: HOST_REGEX, $url, $matches)) {
			if (($host_matches || preg_match(self :: HOST_REGEX, $host, $host_matches))//check is not ip
				&& !is_numeric($host_matches['domain'] ?? null)) {
				self :: $host_matches[$host] = $host_matches;

				$has_subdomain = !empty($host_matches['subdomain']) || ($matches['subdomain'] != '*');
				$has_tld       = !empty($host_matches['tld']) || ($matches['tld'] != '*');

				return $matches;
			}

			//if host is ip number, localhost or does not have tld remove tld and subdomain
			if (is_numeric($host_matches['domain'] ?? null) || $host == 'localhost' || (!str_contains($host, '.'))) {
				$matches['domain']    = $host;
				$matches['subdomain'] = $matches['tld'] = $matches['prefix'] = '';
			}

			if ($host == 'localhost') {
				$matches['domain']    = 'localhost';
				$matches['subdomain'] = $matches['tld'] = $matches['prefix'] = '';
			}

			return $matches;
		}

		if ($url) {
			$hasPort = strpos($url, ':') ?: null;
			$hostWp  = substr($url, 0, strpos($url, ':') ?: null);

			return ['domain' => $hostWp, 'tld' => '', 'subdomain' => '', 'prefix' => ''];
		}

		return [];
	}

	/**
	 * Resolve a URL pattern with wildcards against the current host.
	 *
	 * @param string      $url  URL pattern with wildcards.
	 * @param string|null $host Hostname to resolve against.
	 *
	 * @return string Resolved URL.
	 */
	public static function url(string $url, ?string $host = null): string {
		$host ??= self :: getHost();
		$hasPort = strpos($host, ':') ?: null;
		$hostWp  = substr($host, 0, strpos($host, ':') ?: null);

		$host_matches = self :: $host_matches[$host] ?? [];

		if (preg_match(self :: HOST_REGEX, $url, $matches)) {
			if (($host_matches || preg_match(self :: HOST_REGEX, $host, $host_matches))//check is not ip
				&& !is_numeric($host_matches['domain'] ?? null)) {
				self :: $host_matches[$host] = $host_matches;

				$subdomain = str_replace('*', $host_matches['subdomain'], $matches['subdomain']);
				$domain    = str_replace('*', $host_matches['domain'], $matches['domain']);
				$tld       = str_replace('*', $host_matches['tld'], $matches['tld']);

				return $matches['prefix'] .
					   $subdomain . ($subdomain ? '.' : '') .
					   $domain . ($tld ? '.' : '') . $tld .
					   ($matches['path'] ?? '');
			}

			//if host is ip number, localhost or does not have tld remove tld and subdomain
			if (!($matches['prefix'] || ($matches['tld'] && $matches['tld'] !== '*')) &&
				(is_numeric($host_matches['domain'] ?? null) || $hostWp == 'localhost' || (!str_contains($hostWp, '.')))) {
				$matches['domain']    = $host;
				$matches['subdomain'] = $matches['tld'] = $matches['prefix'] = '';
			}

			$url = ($matches['prefix'] ?: '') .
				   (!empty($matches['subdomain']) ? $matches['subdomain'] . '.' : '') .
				   $matches['domain'] .
				   (!empty($matches['tld']) ? '.' . $matches['tld'] : '') .
				   ($matches['path'] ?? '');
		}

		return $url;
	}

	/**
	 * Normalize a site URL to a key format (dots replaced with spaces).
	 *
	 * @param string $site_url Site URL to normalize.
	 *
	 * @return string Normalized site key.
	 */
	public static function siteKey(string $site_url = ''): string {
		return str_replace('.', ' ', $site_url);
	}

	/**
	 * Set site data by site ID.
	 *
	 * @param int    $site_id Site ID.
	 * @param string $name    Configuration key name.
	 * @param mixed  $value   Value to set.
	 *
	 * @return bool Result of setConfig.
	 */
	public static function setSiteDataById(int $site_id, ?string $name, mixed $value): bool {
		$site = self :: getSiteById($site_id);

		if ($site && ($site['site_id'] == $site_id)) {
			$key = "sites.{$site['key']}";

			//key has changed replace site
			if (isset($value['key']) && ($value['key'] != $site['key'])) {
				$config = \Vvveb\getConfig($key);

				if ($config) {
					$value += $config;
					$config = \Vvveb\unsetConfig($key);
					$key    = "sites.{$value['key']}";
				}
			}

			if ($name) {
				$key .= ".$name";
			}

			self :: $sites = null;

			return \Vvveb\setConfig($key, $value);
		}

		return false;
	}

	/**
	 * Set site data by site key.
	 *
	 * @param string $site_key Site key.
	 * @param string $name     Configuration key name.
	 * @param mixed  $value    Value to set.
	 *
	 * @return mixed Result of setConfig.
	 */
	public static function setSiteDataByKey(string $site_key, string $name, mixed $value): mixed {
		$site = self :: getSiteByKey($site_key);

		$site_key = self :: siteKey($site_key);

		if ($site && ($site['key'] == $site_key)) {
			$key = "sites.{$site['key']}";

			if ($name) {
				$key .= ".$name";
			}

			self :: $sites = null;

			return \Vvveb\setConfig($key, $value);
		}

		return false;
	}

	/**
	 * Set site data by site ID or key.
	 *
	 * @param string|int $site  Site ID or key.
	 * @param string     $name  Configuration key name.
	 * @param mixed      $value Value to set.
	 *
	 * @return mixed Result of setSiteDataById or setSiteDataByKey.
	 */
	public static function setSiteData(string|int $site, string $name, mixed $value): mixed {
		if (is_numeric($site)) {
			return self :: setSiteDataById((int)$site, $name, $value);
		} else {
			return self :: setSiteDataByKey($site, $name, $value);
		}
	}

	/**
	 * Get the current HTTP host.
	 *
	 * @return string Hostname, defaults to 'localhost'.
	 */
	public static function getHost(): string {
		$host = $_SERVER['HTTP_HOST'] ?? 'localhost';

		return $host;
	}

	/**
	 * Resolve request URI and add it to the site result.
	 *
	 * @param array<string, mixed> $result Site data array.
	 * @param string               $path   URL path prefix.
	 *
	 * @return array<string, mixed> Site data with URI.
	 */
	public static function requestUri(array $result, string $path = ''): array {
		$result['host']  = self :: url($result['host']);
		$result['uri']   = $_SERVER['REQUEST_URI'] ?? '/';
		$result['path']  = $path;

		if (V_SUBDIR_INSTALL) {
			$result['uri']  = substr($result['uri'], strlen(V_SUBDIR_INSTALL)) ?: '/';
		}

		if ($path) {
			$result['uri']  = substr($result['uri'], strlen($path)) ?: '/';
			if ($result['uri'][0] != '/') {
				$result['uri'] = '/' . $result['uri'];
			}
		}

		return $result;
	}

	/**
	 * Get site data for the current host, matching by host/path patterns.
	 *
	 * @param string|int|false $site Site ID or key, or false to auto-detect.
	 * @param string           $path URL path prefix.
	 *
	 * @return array<string, mixed>|null Site data or null.
	 */
	public static function getSiteData(string|int|false $site = false, string $path = ''): ?array {
		if (is_numeric($site)) {
			$result = self :: getSiteById((int)$site);
			if ($result) {
				return self :: requestUri($result, $result['path'] ?? '');
			}

			return null;
		}

		if (!$site) {
			$host = self :: getHost();
		} else {
			$host = $site;
		}

		if ($path) {
			if (V_SUBDIR_INSTALL) {
				$path = substr($path, strlen(V_SUBDIR_INSTALL));
			}

			if (preg_match('/(.+?)[$\/\?]/', $path, $matches)) {
				$path = $matches[1];
			}
		}

		if ($path == '/') {
			$path = '';
		}

		$cacheDriver = Cache :: getInstance();
		$cacheKey    = $host;

		if (false /*$result = $cacheDriver->get('site', $cacheKey)*/) {
			return self :: requestUri($result, $result['path'] ?? '');
		} else {
			$host  = self :: siteKey($host);
			$first = strpos($host, ' ');
			$last  = strrpos($host, ' ');

			$subdomain_wildcard    = '* ' . substr($host, $first);
			$tld_wildcard          = substr($host, 0, $last) . ' *';
			$domain_wildcard       = substr($host, 0, $first) . ' * *';
			$full_wildcard         = '* ' . trim(substr($host, $first, $last - $first)) . ' *';
			$hostPath        	     =  $host . $path;

			$sites = \Vvveb\config('sites');
			$match = '* * *';
			$sub   = '';
			foreach ($sites as $id => $site) {
				if ($path && ($id == $host . $path)) {
					$match = $id;
					$sub   = $path;
					break;
				}

				if ($path && ($id == '* * *' . $path)) {
					$match = $id;
					$sub   = $path;
					break;
				}

				if ($id == $host) {
					$match = $id;
					break;
				}

				if ($id == $subdomain_wildcard) {
					$match = $id;
					break;
				}

				if ($id == $domain_wildcard) {
					$match = $id;
					break;
				}

				if ($id == $full_wildcard) {
					$match = $id;
					break;
				}

				if ($id == $tld_wildcard) {
					$match = $id;
					break;
				}
			}

			$result = $sites[$match] ?? [];

			if ($result) {
				return self :: requestUri($result, $sub);
			} else {
				if (APP !== 'app') {
					//if site does not exist use fallback for admin, cli etc
					$result = [
						'host'     => 'localhost',
						'theme'    => 'landing',
						'template' => '',
						'site_id'  => 1,
						'state'    => 'live',
						'uri'      => $_SERVER['REQUEST_URI'] ?? '/',
					];
				}
			}

			$cacheDriver->set('site', $cacheKey, $result);
		}

		return $result;
	}

	/**
	 * Save a site configuration.
	 *
	 * @param array<string, mixed> $site Site data.
	 *
	 * @return void
	 */
	public static function saveSite(array $site): void {
		$key = self :: siteKey(trim($site['key'] ?? $site['host']));
		unset($site['key']);
		\Vvveb\setConfig("sites.$key", $site);
	}

	/**
	 * Delete a site configuration.
	 *
	 * @param array<string, mixed> $site Site data.
	 *
	 * @return mixed Result of unsetConfig.
	 */
	public static function deleteSite(array $site): mixed {
		$key    = self :: siteKey(trim($site['key'] ?? $site['host']));

		return \Vvveb\unsetConfig("sites.$key");
	}
}
