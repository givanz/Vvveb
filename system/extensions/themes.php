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
use \Vvveb\System\Event;
use \Vvveb\System\Sites;
use function Vvveb\__;
use function Vvveb\getDefaultTemplateList;
use function Vvveb\slugify;
use Vvveb\System\Functions\Str;

class Themes extends Extensions {
	static protected $extension = 'theme';

	static protected $baseDir = DIR_THEMES;

	static protected $url = 'https://themes.vvveb.com';

	static protected $feedUrl = 'https://themes.vvveb.com/feed/themes';

	static protected $categoriesFeedUrl = 'https://themes.vvveb.com/feed/categories';

	static protected $themes = [];

	static protected $imageExtensions = ['png', 'jpg', 'webp', 'jpeg'];

	static protected $categories = [];

	static function getInfo($content, $name = false) {
		$params               = parent::getInfo($content, $name);
		$params['screenshot'] = PUBLIC_PATH . 'themes/' . $name . '/screenshot.png';

		return $params;
	}

	static function clearThemesCache($site_id = SITE_ID) {
		$cacheDriver = Cache :: getInstance();
		$cacheKey    = "themes_list_$site_id";
		$cacheDriver->delete('vvveb', $cacheKey);
	}

	static function getList($site_id = false, $cache = true) {
		$cacheDriver = Cache :: getInstance();
		$cacheKey    = "themes_list_$site_id";
		$activeTheme = Sites::getTheme($site_id) ?? 'default';

		if ($cache && $themes = $cacheDriver->get('vvveb', $cacheKey)) {
		} else {
			$list        = glob(DIR_ROOT . '/public/themes/*/index.html', GLOB_NOSORT);

			$themes = [];

			foreach ($list as $file) {
				$folder      = Str::match('@/([^/]+)/[a-z]+.\w+$@', $file);
				$dir         = Str::match('@(.+)/[a-z]+.\w+$@', $file);
				$themeConfig = $dir . DS . 'theme.php';

				if ($folder == 'default') {
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

				$theme['name']       = $theme['name'] ?? ucfirst($theme['folder']);
				$theme['screenshot'] = $theme['screenshot'] ?? (file_exists($dir . DS . 'screenshot.png') ? PUBLIC_PATH . 'themes/' . $folder . '/screenshot.png' : null);

				if (! isset($theme['screenshot'])) {
					$theme['screenshot'] =  PUBLIC_PATH . 'media/extension.svg';
					if ($h = opendir($dir)) {
						while (($file = readdir($h)) !== false) {
							$extension = strtolower(substr($file, strrpos($file, '.') + 1));
							if (in_array($extension, self :: $imageExtensions)) {
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

			static :: $extensions[static :: $extension] = $themes;
		}

		if (isset($themes[$activeTheme])) {
			$theme = $themes[$activeTheme];
			$theme['active'] = true;
			unset($themes[$activeTheme]);
			$themes = [$activeTheme => $theme] + $themes;
		}

		return $themes;
	}

	/*
	 * Add missing templates by copying from available template
	*/
	static function fixIfMissingTemplates($slug) {
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

				if (! file_exists($file)) {
					$dir = dirname($template);

					if ($dir && ! file_exists($dir)) {
						@mkdir(DIR_THEMES . $slug . DS . $dir);
					}

					@copy(DIR_THEMES . $slug . DS . $defaultTemplate, $file);
				}
			}
		}

		//create missing folders
		foreach (['backup', 'css'] as $folder) {
			$dir = DIR_THEMES . $slug . DS . $folder;
			if (! is_dir($dir)) {
				@mkdir($dir);
			}
		}

		//create missing files
		foreach (['css' . DS . 'custom.css', 'css' . DS . 'fonts.css'] as $file) {
			$path = DIR_THEMES . $slug . DS . $file;
			if (! file_exists($path)) {
				@touch($path);
			}
		}
	}

	static function install($extensionZipFile, $slug = false, $validate = false) {
		$extension   = static :: $extension;
		$success     = true;
		$extractTo   = static :: $baseDir;
		$fileCheck   = 'index.html';
		$folder 	    = false;

		$zip = new \ZipArchive();

		if ($zip->open($extensionZipFile) === true) {
			$info       = false;
			$folderName = $zip->getNameIndex(0);

			//search for top level index.html
			for ($i = $zip->numFiles; ($i > 0 && $success == true); $i--) {
				$file = $zip->getNameIndex($i);

				if (strpos($file, $fileCheck) !== false) {
					if (! $folder || strlen($file) < strlen($folder)) {
						$folder = $file;
					}
				}
			}

			if ($folder) {
				if ($folder == 'index.html') {
					$extractTo .= $slug;
				}



				if ($zip->extractTo($extractTo)) {
					$success = $slug;
					if ($folder !== 'index.html') {
						$folder = str_replace('/index.html', '', $folder);
						$slug = slugify($folder);
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

		Event :: trigger(__CLASS__, __FUNCTION__, $extensionZipFile, $success);

		return $success;
	}
}
