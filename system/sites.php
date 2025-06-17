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
	private static $sites = null;

	private static $host_matches = [];

	const HOST_REGEX = '@(?<prefix>https://|http://|//|^)(?<subdomain>.*?)?\.?(?<domain>[^\.]+)\.(?<tld>[^\.]+|[^\.]{2,3}\.[^\.]{2,3})((?<path>/.*)|$)@';

	private static $states;

	/*
	private static $states = [
		'live'        => ['name' => 'Live', 		'template' => 'index.html', 'icon' => 'la-broadcast-tower'],
		'maintenance' => ['name' => 'Maintenance', 'template' => 'index.maintenance.html', 'icon' => 'la-wrench'],
		'coming-soon' => ['name' => 'Coming soon', 'template' => 'index.coming-soon.html', 'icon' => 'la-clock'],
	];
	*/

	public static function getStates() {
		static :: $states = [
			'live'        => ['name' => __('Live'), 	   'template' => 'index.html',			   'icon' => 'la-broadcast-tower'],
			'maintenance' => ['name' => __('Maintenance'), 'template' => 'index.maintenance.html', 'icon' => 'la-wrench'],
			'coming-soon' => ['name' => __('Coming soon'), 'template' => 'index.coming-soon.html', 'icon' => 'la-clock'],
		];

		return static :: $states;
	}

	public static function getDefault() {
		$sites = self :: getSites();

		return current($sites);
	}

	public static function getSites() {
		if (! self :: $sites) {
			self :: $sites = \Vvveb\config('sites');

			foreach (self::$sites as &$site) {
				$site['url'] = self :: url($site['host']) . (V_SUBDIR_INSTALL ? V_SUBDIR_INSTALL : '');
			}

			return self :: $sites;
		}

		return self :: $sites;
	}

	public static function getSiteByKey($site_key) {
		$sites = self :: getSites();

		$site_key = self :: siteKey($site_key);

		if (isset($sites[$site_key])) {
			$site        = $sites[$site_key];
			$site['key'] = $site_key;

			return $site;
		}

		return false;
	}

	public static function getSiteById($site_id) {
		$sites = self :: getSites();

		foreach ($sites as $site_key => $site) {
			if ($site['id'] == $site_id) {
				$site['key'] = $site_key;

				return $site;
			}
		}
	}

	public static function getTheme($site = false) {
		$data = self :: getSiteData($site);

		if ($data) {
			return $data['theme'];
		}

		return 'default';
	}

	public static function setTheme($site, $theme, $template = '') {
		if ($template) {
			self :: setSiteData($site, 'template', $template);
		}

		return self :: setSiteData($site, 'theme', $theme);
	}

	public static function urlSplit($url = null) {
		$url          = $url ?? $_SERVER['HTTP_HOST'] ?? '';
		$host         = $host ?? self :: getHost();
		$host_matches = self :: $host_matches[$host] ?? [];

		if (preg_match(self :: HOST_REGEX, $url, $matches)) {
			if (($host_matches || preg_match(self :: HOST_REGEX, $host, $host_matches))//check is not ip
				&& ! is_numeric($host_matches['domain'] ?? null)) {
				self :: $host_matches[$host] = $host_matches;

				$has_subdomain = ! empty($host_matches['subdomain']) || ($matches['subdomain'] != '*');
				$has_tld       = ! empty($host_matches['tld']) || ($matches['tld'] != '*');

				return $matches;
			}

			//if host is ip number, localhost or does not have tld remove tld and subdomain
			if (is_numeric($host_matches['domain'] ?? null) || $host == 'localhost' || (strpos($host, '.') === false)) {
				$matches['domain']    = $host;
				$matches['subdomain'] = $matches['tld'] = $matches['prefix'] = '';
			}

			if ($host == 'localhost') {
				$matches['domain']    = 'localhost';
				$matches['subdomain'] = $matches['tld'] = $matches['prefix'] = '';
			}

			return $matches;
		}

		return [];
	}

	public static function url($url, $host = null) {
		$host    = $host ?? self :: getHost();
		$hasPort = strpos($host, ':') ?: null;
		$hostWp  = substr($host, 0, strpos($host, ':') ?: null);

		$host_matches = self :: $host_matches[$host] ?? [];

		if (preg_match(self :: HOST_REGEX, $url, $matches)) {
			if (($host_matches || preg_match(self :: HOST_REGEX, $host, $host_matches))//check is not ip
				&& ! is_numeric($host_matches['domain'] ?? null)) {
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
			if (! ($matches['prefix'] || ($matches['tld'] && $matches['tld'] !== '*')) &&
				(is_numeric($host_matches['domain'] ?? null) || $hostWp == 'localhost' || (strpos($hostWp, '.') === false))) {
				$matches['domain']    = $host;
				$matches['subdomain'] = $matches['tld'] = $matches['prefix'] = '';
			}

			$url = ($matches['prefix'] ? $matches['prefix'] : '') .
				   (! empty($matches['subdomain']) ? $matches['subdomain'] . '.' : '') .
				   ($matches['domain'] ?? '') .
				   (! empty($matches['tld']) ? '.' . $matches['tld'] : '') .
				   ($matches['path'] ?? '');
		}

		return $url;
	}

	public static function siteKey($site_url = false) {
		return str_replace('.', ' ',$site_url);
	}

	public static function setSiteDataById($site_id, $name, $value) {
		$site = self :: getSiteById($site_id);

		if ($site && ($site['id'] == $site_id)) {
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

			return \Vvveb\setConfig($key, $value);
		}

		return false;
	}

	public static function setSiteDataByKey($site_key, $name, $value) {
		$site = self :: getSiteByKey($site_key);

		$site_key = self :: siteKey($site_key);

		if ($site && ($site['key'] == $site_key)) {
			$key = "sites.{$site['key']}";

			if ($name) {
				$key .= ".$name";
			}

			return \Vvveb\setConfig($key, $value);
		}

		return false;
	}

	public static function setSiteData($site, $name, $value) {
		if (is_int($site)) {
			return self :: setSiteDataById($site, $name, $value);
		} else {
			return self :: setSiteDataByKey($site, $name, $value);
		}
	}

	public static function getHost() {
		$host =$_SERVER['HTTP_HOST'] ?? 'localhost';

		return $host;
	}

	public static function getSiteData($site_url = false) {
		if (is_int($site_url)) {
			return self :: getSiteById($site_url);
		}

		if (! $site_url) {
			$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
		}

		$host = self :: siteKey($host);

		$first = strpos($host, ' ');
		$last  = strrpos($host, ' ');

		$subdomain_wildcard    = '* ' . substr($host, $first);
		$tld_wildcard          = substr($host, 0, $last) . ' *';
		$domain_wildcard       = substr($host, 0, $first) . ' *';
		$full_wildcard         = '* ' . trim(substr($host, $first, $last - $first)) . ' *';

		$result = \Vvveb\config("sites.$host", null) ??
				  \Vvveb\config("sites.$subdomain_wildcard", null) ??
				  \Vvveb\config("sites.$domain_wildcard", null) ??
				  \Vvveb\config("sites.$full_wildcard", null) ??
				  \Vvveb\config("sites.$tld_wildcard", null) ??
				  \Vvveb\config('sites.* * *', null);

		if ($result) {
			$result['host'] = self :: url($result['host']);
		} else {
			if (APP !== 'app') {
				//if site does not exist use fallback for admin, cli etc
				return [
					'host'     => 'localhost',
					'theme'    => 'landing',
					'template' => '',
					'id'       => 1,
					'state'    => 'live',
				];
			}
		}

		return $result;
	}

	public static function saveSite($site) {
		$key = self :: siteKey(trim($site['key'] ?? $site['host']));
		unset($site['key']);
		$return = \Vvveb\setConfig("sites.$key", $site);
	}

	public static function deleteSite($site) {
		$key    = self :: siteKey(trim($site['key'] ?? $site['host']));
		$return = \Vvveb\unsetConfig("sites.$key", $site);
	}
}
