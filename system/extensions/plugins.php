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

use function Vvveb\rcopy;
use function Vvveb\rrmdir;
use Vvveb\System\Cache;
use Vvveb\System\Event;
use Vvveb\System\Notifications;

class Plugins extends Extensions {
	/**
	 * @var string
	 */
	protected static string $url = 'https://www.vvveb.com';

	/**
	 * @var string
	 */
	protected static string $feedUrl = 'https://plugins.vvveb.com/feed/plugins';

	/**
	 * @var string
	 */
	protected static string $categoriesFeedUrl = 'https://plugins.vvveb.com/feed/categories';

	/**
	 * @var string
	 */
	protected static string $extension = 'plugin';

	/**
	 * @var bool
	 */
	protected static bool $loaded  = false;

	/**
	 * @var string
	 */
	protected static string $baseDir = DIR_PLUGINS;

	/**
	 * @var array<array-key, mixed>
	 */
	protected static array $plugins = [];

	/**
	 * @var array<array-key, mixed>
	 */
	protected static array $categories = [];

	/**
	 * @param string $pluginName
	 * @param int    $site_id
	 *
	 * @return void
	 */
	/**
	 * Validate a plugin name to prevent path traversal.
	 */
	protected static function sanitizeName(string $pluginName): string {
		return preg_replace('/[^a-zA-Z0-9_\\-]/', '', trim($pluginName, '/\\'));
	}

	public static function clearPluginsCache(int $site_id = SITE_ID): void {
		$cacheDriver = Cache::getInstance();
		$cacheKey    = "plugins_list_$site_id";
		$cacheDriver->delete('vvveb', $cacheKey);
	}

	/**
	 * @param string       $content
	 * @param string|false $name
	 *
	 * @return array<array-key, mixed>
	 */
	public static function getInfo(string $content, string|false $name = false): array {
		$params               = parent::getInfo($content, $name);
		$params['status']     = 'inactive';

		if (isset($params['thumb']) &&
			file_exists(DIR_PUBLIC . ($file = 'plugins/' . $name . '/' . $params['thumb']))) {
			$params['thumb_url'] = PUBLIC_PATH . $file;
		} else {
			$params['thumb_url'] = PUBLIC_PATH . 'media/extension.svg';
		}

		return $params;
	}

	/**
	 * @param string $pluginName
	 *
	 * @return mixed
	 */
	public static function loadPlugin(string $pluginName): mixed {
		$pluginName = self::sanitizeName($pluginName);
		$file       = DIR_PLUGINS . $pluginName . '/plugin.php';

		if (file_exists($file)) {
			return include $file;
		}

		return false;
	}

	/**
	 * @param string $pluginName
	 * @param int    $site_id
	 *
	 * @return mixed
	 */
	public static function activate(string $pluginName, int $site_id = SITE_ID): mixed {
		$pluginName = self::sanitizeName($pluginName);
		if (!$pluginName) {
			return null;
		}

		$file = DIR_PLUGINS . $pluginName . '/plugin.php';

		if (file_exists($file)) {
			$key    = "plugins.$site_id.$pluginName.status";
			$status = \Vvveb\getConfig($key);

			if (!$status) {
				//if no plugin info then this is first activation, run plugin setup
				Event::trigger(__CLASS__, 'setup', $pluginName, $site_id);
			}

			$return = \Vvveb\setConfig($key, 'active');
			Event::trigger(__CLASS__, __FUNCTION__, $pluginName, $site_id);

			if ($return) {
				Notifications::trigger('plugin.activate', ['subject' => sprintf('Plugin activated: %s', $pluginName), 'message' => sprintf('Plugin "%s" was activated.', $pluginName), 'plugin' => $pluginName]);
			}

			self::clearPluginsCache($site_id);
			self::copyPublicDir($pluginName);

			return $return;
		}

		return false;
	}

	/**
	 * @param string $pluginName
	 * @param int    $site_id
	 *
	 * @return mixed
	 */
	public static function deactivate(string $pluginName, int $site_id = SITE_ID): mixed {
		$pluginName = self::sanitizeName($pluginName);
		if (!$pluginName) {
			return null;
		}
		$key    = "plugins.$site_id.$pluginName.status";
		$return = \Vvveb\setConfig($key, 'inactive');
		Event::trigger(__CLASS__, __FUNCTION__, $pluginName, $site_id);

		if ($return) {
			Notifications::trigger('plugin.deactivate', ['subject' => sprintf('Plugin deactivated: %s', $pluginName), 'message' => sprintf('Plugin "%s" was deactivated.', $pluginName), 'plugin' => $pluginName]);
		}

		self::clearPluginsCache($site_id);

		return $return;
	}

	//copy plugin public folder to public/plugins folder

	/**
	 * @param string $pluginName
	 *
	 * @return bool
	 */
	public static function copyPublicDir(string $pluginName): bool {
		if (!$pluginName) {
			return false;
		}

		$publicSrc  = DIR_PLUGINS . "$pluginName/public";
		$publicDest = DIR_PUBLIC . "plugins/$pluginName";

		if (is_link($publicDest)) {
			return true;
		}

		if (!file_exists($publicDest) && function_exists('symlink') && \symlink($publicSrc, $publicDest)) {
			//try symlink first
			return true;
		}

		return rcopy($publicSrc, $publicDest);
	}

	/**
	 * @param string       $zipFile
	 * @param string|false $slug
	 * @param bool         $validate
	 *
	 * @return string|bool
	 */
	public static function install(string $zipFile, string|false $slug = false, bool $validate = true): string|bool {
		if ($pluginName = parent::install($zipFile, $slug, $validate)) {
			if (self::copyPublicDir($pluginName)) {
				self::clearPluginsCache();

				Notifications::trigger('plugin.add', ['subject' => sprintf('Plugin installed: %s', $pluginName), 'message' => sprintf('Plugin "%s" was installed.', $pluginName), 'plugin' => $pluginName]);

				return $pluginName;
			}
		}

		return false;
	}

	/**
	 * @param string $pluginName
	 * @param int    $site_id
	 *
	 * @return bool
	 */
	public static function uninstall(string $pluginName, int $site_id = SITE_ID): bool {
		$pluginName = self::sanitizeName($pluginName);
		$success    = false;
		//remove public folder from public/plugins/$pluginName
		$pluginDir  = DIR_PLUGINS . "$pluginName";
		$publicDir  = DIR_PUBLIC . "plugins/$pluginName";

		if (file_exists($publicDir)) {
			if (is_link($publicDir)) {
				unlink($publicDir);
			} else {
				if (is_dir($publicDir)) {
					rrmdir($publicDir);
				}
			}
		}

		$success = rrmdir($pluginDir);

		$key    = "plugins.$site_id.$pluginName";
		\Vvveb\unsetConfig($key);
		self::clearPluginsCache($site_id);

		Event::trigger(__CLASS__, __FUNCTION__, $pluginName, $success);

		if ($success) {
			Notifications::trigger('plugin.delete', ['subject' => sprintf('Plugin uninstalled: %s', $pluginName), 'message' => sprintf('Plugin "%s" was uninstalled.', $pluginName), 'plugin' => $pluginName]);
		}

		return $success;
	}

	/**
	 * @param int          $site_id
	 * @param string|false $category
	 * @param bool         $cache
	 *
	 * @return array<array-key, mixed>
	 */
	public static function getList(int $site_id = SITE_ID, string|false $category = false, bool $cache = true): array {
		$cacheDriver = Cache::getInstance();
		$cacheKey    = "plugins_list_$site_id";

		if (!$category && $cache && $result = $cacheDriver->get('vvveb', $cacheKey)) {
			return $result;
		} else {
			$pluginList   = parent::getListInfo(DIR_PLUGINS . '/*/plugin.php');
			$pluginConfig = [];

			if ($site_id) {
				$pluginConfig = \Vvveb\config("plugins.$site_id", []);
			}

			if (is_array($pluginConfig)) {
				$pluginList = array_replace_recursive($pluginList, $pluginConfig);
			}

			//set default name to show the plugin as broken if is missing
			array_walk($pluginList, function(&$val, $key) use (&$pluginList, $category): void {
				if (!isset($val['name'])) {
					$val['slug']   = $key;
					$val['name']   = sprintf('[%s]', $key);
					$val['status'] = 'broken';
				}

				if ($category && (!isset($val['category']) || ($val['category'] !== $category))) {
					unset($pluginList[$key]);
				}
			});

			if (!$category && $cache) {
				$cacheDriver->set('vvveb', $cacheKey, $pluginList);
				$cacheDriver->set('vvveb', "{$cacheKey}_categories", static::$categories);
			}

			return $pluginList;
		}
	}

	/**
	 * @param int $site_id
	 *
	 * @return array<array-key, mixed>
	 */
	public static function getCategories(int $site_id = SITE_ID): array {
		if (!static::$categories) {
			$cacheDriver          = Cache::getInstance();
			$cacheKey             = "plugins_list_{$site_id}_categories";
			static::$categories   = $cacheDriver->get('vvveb', $cacheKey) ?? [];
		}

		return static::$categories;
	}

	/**
	 * @param int $site_id
	 *
	 * @return void
	 */
	public static function loadPlugins(int $site_id = SITE_ID): void {
		if (static::$loaded) {
			return;
		}
		static::$loaded = true;

		$plugins = static::getList($site_id);

		foreach ($plugins as $name => $plugin) {
			if ((isset($plugin['status']) && $plugin['status'] === 'active')
				&& file_exists($plugin['file'])) {
				include $plugin['file'];
			}
		}
	}
}
