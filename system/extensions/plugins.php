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

namespace Vvveb\System\Extensions;

use \Vvveb\System\Cache;
use function Vvveb\rcopy;
use function Vvveb\rrmdir;
use Vvveb\System\Event;

class Plugins extends Extensions {
	static protected $url = 'https://plugins.vvveb.com';

	static protected $feedUrl = 'https://plugins.vvveb.com/feed/plugins';

	static protected $extension = 'plugin';

	static protected $loaded  = false;

	static protected $baseDir = DIR_PLUGINS;

	static protected $plugins = [];

	static protected $categories = [];

	static function clearPluginsCache($site_id = SITE_ID) {
		$cacheDriver = Cache :: getInstance();
		$cacheKey    = "plugins_list_$site_id";
		$cacheDriver->delete('vvveb', $cacheKey);
	}

	static function getInfo($content, $name = false) {
		$params               = parent::getInfo($content, $name);
		$params['status']     = 'inactive';

		if (isset($params['thumb'])) {
			$params['thumb_url'] = PUBLIC_PATH . 'plugins/' . $name . '/' . $params['thumb'];
		} else {
			//$params['thumb_url'] = PUBLIC_PATH . 'plugins/plugin.svg';
		}

		return $params;
	}

	static function loadPlugin($pluginName) {
		$file = DIR_PLUGINS . $pluginName . '/plugin.php';

		if (file_exists($file)) {
			return include $file;
		}

		return false;
	}

	static function activate($pluginName, $site_id = SITE_ID) {
		if (! $pluginName) {
			return;
		}

		$file = DIR_PLUGINS . $pluginName . '/plugin.php';

		if (file_exists($file)) {
			$key    = "plugins.$site_id.$pluginName.status";
			$status = \Vvveb\get_config($key);

			if (! $status) {
				//if no plugin info then this is first activation, run plugin setup
				Event :: trigger(__CLASS__, 'setup', $pluginName, $site_id);
			}

			$return = \Vvveb\set_config($key, 'active');
			Event :: trigger(__CLASS__, __FUNCTION__, $pluginName, $site_id);

			self :: clearPluginsCache($site_id);
			self :: copyPublicDir($pluginName);

			return $return;
		}

		return false;
	}

	static function deactivate($pluginName, $site_id = SITE_ID) {
		if (! $pluginName) {
			return;
		}
		$key    = "plugins.$site_id.$pluginName.status";
		$return = \Vvveb\set_config($key, 'inactive');
		/*
		$key    = "plugins.$site_id.$pluginName";
		$return = \Vvveb\unset_config($key, []);
		*/
		Event :: trigger(__CLASS__, __FUNCTION__, $pluginName, $site_id);

		self :: clearPluginsCache($site_id);

		return $return;
	}

	//copy plugin public folder to public/plugins folder
	static function copyPublicDir($pluginName) {
		if ($pluginName) {
			$publicSrc  = DIR_PLUGINS . "$pluginName/public";
			$publicDest = DIR_PUBLIC . "plugins/$pluginName";

			if (! file_exists($publicDest)) {
				return rcopy($publicSrc, $publicDest);
			} else {
				return false;
			}
		}
	}

	static function install($zipFile, $slug = false, $validate = true) {
		$pluginName = parent :: install($zipFile, $validate);

		self :: copyPublicDir($pluginName);
		self :: clearPluginsCache($site_id);

		return $pluginName;
	}

	static function uninstall($pluginName, $site_id = SITE_ID) {
		$success = false;
		//remove public folder from public/plugins/$pluginName
		$pluginDir  = DIR_PLUGINS . "$pluginName";
		$publicDir  = DIR_PUBLIC . "plugins/$pluginName";

		rrmdir($publicDir);
		$success = rrmdir($pluginDir);

		$key    = "plugins.$site_id.$pluginName";
		\Vvveb\unset_config($key, []);
		self :: clearPluginsCache($site_id);

		Event :: trigger(__CLASS__, __FUNCTION__, $pluginName, $success);

		return $success;
	}

	static function getList($site_id = SITE_ID, $category = false, $cache = true) {
		$cacheDriver = Cache :: getInstance();
		$cacheKey    = "plugins_list_$site_id";

		if (! $category && $cache && $result = $cacheDriver->get('vvveb', $cacheKey)) {
			return $result;
		} else {
			$pluginList   = parent :: getListInfo(DIR_PLUGINS . '/*/plugin.php');
			$pluginConfig = [];

			if ($site_id) {
				$pluginConfig = \Vvveb\config("plugins.$site_id", []);
			}

			if (is_array($pluginConfig)) {
				$pluginList = array_replace_recursive($pluginList, $pluginConfig);
			}

			//set default name to show the plugin as broken if is missing
			array_walk($pluginList, function (&$val, $key) use (&$pluginList, $category) {
				if (! isset($val['name'])) {
					$val['slug'] = $key;
					$val['name'] = sprintf('[%s]', $key);
					$val['status'] = 'broken';
				}

				if ($category && (! isset($val['category']) || ($val['category'] != $category))) {
					unset($pluginList[$key]);
				}
			});

			if (! $category && $cache) {
				$cacheDriver->set('vvveb', $cacheKey, $pluginList);
				$cacheDriver->set('vvveb', "{$cacheKey}_categories", static :: $categories);
			}

			return $pluginList;
		}
	}

	static function getCategories($site_id = SITE_ID) {
		if (! static :: $categories) {
			$cacheDriver          = Cache :: getInstance();
			$cacheKey             = "plugins_list_{$site_id}_categories";
			static :: $categories = $cacheDriver->get('vvveb', $cacheKey);
		}

		return static :: $categories;
	}

	static function loadPlugins($site_id = SITE_ID) {
		if (static :: $loaded) {
			return;
		}
		static :: $loaded = true;

		$plugins = static::getList($site_id);

		foreach ($plugins as $name => $plugin) {
			if ((isset($plugin['status']) && $plugin['status'] == 'active')
				&& file_exists($plugin['file'])) {
				include $plugin['file'];
			}
		}
	}
}
