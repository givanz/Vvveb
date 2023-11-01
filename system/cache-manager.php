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

class CacheManager {
	private static function clearFolder($folder, $root = DIR_PUBLIC) {
		$name = $root . $folder . DS;

		$name .= '{*,*/*,*/*/*}';

		$files = glob($name, GLOB_BRACE);

		if ($files) {
			foreach ($files as $file) {
				if ($file[0] === '.') {
					continue;
				}

				if (! @unlink($file)) {
					clearstatcache(false, $file);
				}
			}
		}

		return true;
	}

	public static function clearFrontend() {
		return self :: clearFolder('assets-cache');
	}

	public static function clearModelCache() {
		return self :: clearFolder('model' . DS . 'app', DIR_STORAGE) &&
			   self :: clearFolder('model' . DS . 'admin', DIR_STORAGE);
	}

	public static function clearImageCache() {
		return self :: clearFolder('image-cache');
	}

	public static function clearCompiledFiles($app = false, $site_id = false, $theme = false, $module = false) {
		$name = DIR_COMPILED_TEMPLATES;

		if ($app) {
			$name .= "{$app}_";
		}

		if ($site_id) {
			$name .= "{$site}_id_";
		}

		if ($theme) {
			$name .= "{$theme}_";
		}

		if ($module) {
			$name .= $module;
		}

		$name .= '*';

		$files = glob($name);

		if ($files) {
			foreach ($files as $file) {
				if ($file[0] === '.') {
					continue;
				}

				if (! @unlink($file)) {
					clearstatcache(false, $file);
				}
			}
		}

		return true;
	}

	public static function clearObjectCache($namespace = '') {
		$cacheDriver = Cache::getInstance();

		return $cacheDriver->delete($namespace);
	}

	public static function clearPageCache($namespace = '') {
		$pageCache = PageCache::getInstance();
		$pageCache->purge($namespace);
	}

	public static function delete($namespace = '') {
		//self :: clearModelCache($namespace);
		self :: clearObjectCache($namespace);
		self :: clearCompiledFiles();
		self :: clearPageCache($namespace);

		return true;
	}
}
