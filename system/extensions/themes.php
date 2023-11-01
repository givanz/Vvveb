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

use \Vvveb\System\Event;
use \Vvveb\System\Sites;
use Vvveb\__;
use Vvveb\System\Functions\Str;

class Themes extends Extensions {
	static protected $extension = 'theme';

	static protected $baseDir = DIR_THEMES;

	static protected $url = 'https://themes.vvveb.com';

	static protected $feedUrl = 'https://themes.vvveb.com/feed/themes';

	static protected $themes = [];

	static protected $categories = [];

	static function getInfo($content, $name = false) {
		$params               = parent::getInfo($content, $name);
		$params['screenshot'] = PUBLIC_PATH . 'themes/' . $name . '/screenshot.png';

		return $params;
	}

	static function getList($path = '') {
		$activeTheme = Sites::getTheme() ?? 'default';
		$list        = glob(DIR_ROOT . '/public/themes/*/index.html');

		foreach ($list as $file) {
			$folder      = Str::match('@/([^/]+)/[a-z]+.\w+$@', $file);
			$dir         = Str::match('@(.+)/[a-z]+.\w+$@', $file);
			$themeConfig = $dir . DS . 'theme.php';

			$theme           = [];
			$theme['file']   = $file;
			$theme['folder'] = $folder;
			$theme['import'] = false;
			$theme['author'] = 'n/a';

			if (file_exists($themeConfig)) {
				$content         = file_get_contents($themeConfig);
				$theme           = static::getInfo($content, $folder) + $theme;
				$theme['import'] = file_exists($dir . DS . 'import');
			}

			$theme['name']       = $theme['name'] ?? ucfirst($theme['folder']);
			$theme['screenshot'] = $theme['screenshot'] ?? '/../media/placeholder.svg';

			$themes[$folder] = $theme;

			if ($theme['active'] = ($activeTheme == $theme['folder'])) {
				unset($themes[$activeTheme]);
				$themes = [$activeTheme => $theme] + $themes;
			}
		}

		static :: $extensions[static :: $extension] = $themes;

		return $themes;
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
					$extractTo .= DS . $slug;
				}

				if ($zip->extractTo($extractTo)) {
					$success = $slug;
				} else {
					$success = false;
				}
			} else {
				throw new \Exception(sprintf(__('No `%s` info found inside zip!', $fileCheck)));
			}

			$zip->close();
		} else {
			throw new \Exception(__('File is not a valid zip archive!'));
		}

		Event :: trigger(__CLASS__, __FUNCTION__, $extensionZipFile, $success);

		return $success;
	}
}
