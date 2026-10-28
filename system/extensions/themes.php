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

use function Vvveb\__;
use function Vvveb\getDefaultTemplateList;
use function Vvveb\slugify;
use Vvveb\System\Cache;
use Vvveb\System\Event;
use Vvveb\System\Functions\Str;
use Vvveb\System\Notifications;
use Vvveb\System\Sites;

class Themes extends Extensions {
	/** @var string */
	protected static string $extension = 'theme';

	/** @var string */
	protected static string $baseDir = DIR_THEMES;

	/** @var string */
	protected static string $url = 'https://themes.vvveb.com';

	/** @var string */
	protected static string $feedUrl = 'https://themes.vvveb.com/feed/themes';

	/** @var string */
	protected static string $categoriesFeedUrl = 'https://themes.vvveb.com/feed/categories';

	/** @var array<array-key, mixed> */
	protected static array $themes = [];

	/** @var array<array-key, mixed> */
	protected static array $imageExtensions = ['png', 'jpg', 'webp', 'jpeg'];

	/** @var array<array-key, mixed> */
	protected static array $categories = [];

	/**
	 * @param string       $content
	 * @param string|false $name
	 *
	 * @return array<array-key, mixed>
	 */
	public static function getInfo(string $content, string|false $name = false): array {
		$params               = parent::getInfo($content, $name);
		$params['screenshot'] = PUBLIC_PATH . 'themes/' . $name . '/screenshot.png';

		return $params;
	}

	/**
	 * @param int $site_id
	 *
	 * @return void
	 */
	public static function clearThemesCache(int $site_id = SITE_ID): void {
		$cacheDriver = Cache::getInstance();
		$cacheKey    = "themes_list_$site_id";
		$cacheDriver->delete('vvveb', $cacheKey);
	}

	/**
	 * @param int|false $site_id
	 * @param bool      $cache
	 *
	 * @return array<array-key, mixed>
	 */
	public static function getList(int|false $site_id = false, bool $cache = true): array {
		$cacheDriver = Cache::getInstance();
		$cacheKey    = "themes_list_$site_id";
		$activeTheme = Sites::getTheme($site_id);

		if ($cache && $themes = $cacheDriver->get('vvveb', $cacheKey)) {
		} else {
			$list        = glob(DIR_ROOT . '/public/themes/*/index.html', GLOB_NOSORT);

			$themes = [];

			foreach ($list as $file) {
				$folder      = Str::match('@/([^/]+)/[a-z]+.\w+$@', $file);
				$dir         = Str::match('@(.+)/[a-z]+.\w+$@', $file);
				$themeConfig = $dir . DS . 'theme.json';

				if ($folder === 'default') {
					continue;
				}
				$theme           = [];
				$theme['file']   = $file;
				$theme['folder'] = $folder;
				$theme['slug']   = $folder;
				$theme['import'] = false;
				$theme['author'] = 'n/a';

				if (file_exists($themeConfig)) {
					$content         = file_get_contents($themeConfig);
					$theme           = static::getInfo($content, $folder) + $theme;
					$theme['import'] = file_exists($dir . DS . 'import');
				}

				$theme['name'] ??= ucfirst($theme['folder']);
				$theme['screenshot'] = $theme['screenshot'] ?? (file_exists($dir . DS . 'screenshot.png') ? PUBLIC_PATH . 'themes/' . $folder . '/screenshot.png' : null);

				if (!isset($theme['screenshot'])) {
					$theme['screenshot'] =  PUBLIC_PATH . 'media/extension.svg';
					if ($h = opendir($dir)) {
						while (($file = readdir($h)) !== false) {
							if (in_array(strtolower(pathinfo($file, PATHINFO_EXTENSION)), self::$imageExtensions, true)) {
								$theme['screenshot'] = PUBLIC_PATH . 'themes/' . $folder . '/' . $file;
								break;
							}
						}
						closedir($h);
					}
				}

				$themes[$folder] = $theme;
			}

			//show newest themes first
			//$themes = array_reverse($themes);

			if ($cache) {
				$cacheDriver->set('vvveb', $cacheKey, $themes);
			}

			static::$extensions[static::$extension] = $themes;
		}

		if (isset($themes[$activeTheme])) {
			$theme           = $themes[$activeTheme];
			$theme['active'] = true;
			unset($themes[$activeTheme]);
			$themes = [$activeTheme => $theme] + $themes;
		}

		return $themes;
	}

	// Add missing templates by copying from available template

	/**
	 * @param string $slug
	 *
	 * @return void
	 */
	public static function fixIfMissingTemplates(string $slug): void {
		//check templates in order
		$defaultTemplate = '';

		foreach (['content' . DS . 'page.html', 'blank.html', 'index.html'] as $page) {
			if (file_exists(DIR_THEMES . $slug . DS . $page)) {
				$defaultTemplate = $page;

				continue;
			}
		}

		if ($defaultTemplate) {
			$templates = getDefaultTemplateList();

			foreach ($templates as $template) {
				$file = DIR_THEMES . $slug . DS . $template;

				if (!file_exists($file)) {
					$dir = DIR_THEMES . $slug . DS . dirname($template);

					if (!is_dir($dir)) {
						@mkdir($dir, 0777, true);
					}

					@copy(DIR_THEMES . $slug . DS . $defaultTemplate, $file);
				}
			}
		}

		//create missing folders
		foreach (['backup', 'css'] as $folder) {
			$dir = DIR_THEMES . $slug . DS . $folder;
			if (!is_dir($dir)) {
				@mkdir($dir);
			}
		}

		//create missing files
		foreach (['css' . DS . 'custom.css', 'css' . DS . 'fonts.css'] as $file) {
			$path = DIR_THEMES . $slug . DS . $file;
			if (!file_exists($path)) {
				@touch($path);
			}
		}
	}

	/**
	 * @param string       $extensionZipFile
	 * @param string|false $slug
	 * @param bool         $validate
	 *
	 * @return string|bool
	 */
	public static function install(string $extensionZipFile, string|false $slug = false, bool $validate = false): string|bool {
		$success      = true;
		$extractTo    = static::$baseDir;
		$fileCheck    = 'index.html';
		$folder       = false;
		$invalidFiles = [];

		$zip = new \ZipArchive();

		if ($zip->open($extensionZipFile) === true) {
			//search for top level index.html
			for ($i = 0; (($i < $zip->numFiles) && $success == true); $i++) {
				$file = $zip->getNameIndex($i);

				if (!self::validFile($file)) {
					$invalidFiles[] = $file;
				}

				if (str_contains($file, $fileCheck)) {
					if (!$folder || (strlen($file) < strlen($folder))) {
						$folder = $file;
					}
				}
			}

			if ($folder) {
				if ($folder === 'index.html') {
					$extractTo .= $slug;
				}

				$extractTo = preg_replace('@[\\\/]([^\\\/]+?)\.\w+$@', '', $extractTo);
				if ($zip->extractTo($extractTo)) {
					if ($invalidFiles) {
						foreach ($invalidFiles as $file) {
							unlink($extractTo . DS . $file);
						}
					}

					if ($folder !== 'index.html') {
						$folder = preg_replace('@[\\\/]([^\\\/]+?)\.\w+$@', '', $folder);
						$slug   = slugify($folder);

						rename($extractTo . $folder, $extractTo . $slug);
					}
				} else {
					$success = false;
				}
			} else {
				throw new \Exception(sprintf(__('No `%s` info found inside zip!'), $fileCheck));
			}

			$zip->close();
		} else {
			throw new \Exception(__('File is not a valid zip archive!'));
		}

		Event::trigger(__CLASS__, __FUNCTION__, $extensionZipFile, $slug, $success);

		if ($success) {
			Notifications::trigger('theme.add', ['subject' => sprintf('Theme installed: %s', $slug ?: $extensionZipFile), 'message' => sprintf('Theme "%s" was installed.', $slug ?: $extensionZipFile), 'theme' => $slug ?: $extensionZipFile]);
		}

		return $success;
	}
}
