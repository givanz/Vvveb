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

use function Vvveb\globBrace;

class CacheManager {
	/**
	 * Delete all files in a cache folder.
	 *
	 * @param string $folder Relative folder path under the root directory.
	 * @param string $root   Root directory path.
	 *
	 * @return bool Always returns true.
	 */
	private static function clearFolder(string $folder, string $root = DIR_PUBLIC): bool {
		$name = $root . $folder . DS;

		$glob = ['*', '*/*', '*/*/*'];

		$files = globBrace($name, $glob);

		if ($files) {
			foreach ($files as $file) {
				if ($file[0] === '.') {
					continue;
				}

				if (!@unlink($file)) {
					clearstatcache(false, $file);
				}
			}
		}

		return true;
	}

	/**
	 * Clear the frontend assets cache.
	 *
	 * @return bool Result of clearFolder.
	 */
	public static function clearFrontend(): bool {
		return self :: clearFolder('assets-cache');
	}

	/**
	 * Clear the model cache files.
	 *
	 * @return bool Result of clearFolder.
	 */
	public static function clearModelCache(): bool {
		return self :: clearFolder('model' . DS . 'app', DIR_STORAGE) &&
			   self :: clearFolder('model' . DS . 'admin', DIR_STORAGE);
	}

	/**
	 * Clear the image cache.
	 *
	 * @return bool Result of clearFolder.
	 */
	public static function clearImageCache(): bool {
		return self :: clearFolder('image-cache');
	}

	/**
	 * Clear compiled template files, optionally filtered by criteria.
	 *
	 * @param string|false $app     App name filter.
	 * @param int|false    $site_id Site ID filter.
	 * @param string|false $theme   Theme filter.
	 * @param string|false $module  Module filter.
	 *
	 * @return bool Always returns true.
	 */
	public static function clearCompiledFiles(string|false $app = false, int|false $site_id = false, string|false $theme = false, string|false $module = false): bool {
		$name = DIR_COMPILED_TEMPLATES;

		if ($app) {
			$name .= "{$app}_";
		}

		if ($site_id) {
			$name .= "{$site_id}_";
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

				if (!@unlink($file)) {
					clearstatcache(false, $file);
				}
			}
		}

		return true;
	}

	/**
	 * Clear the object cache for a given namespace.
	 *
	 * @param string       $namespace Cache namespace.
	 * @param string|false $key       Specific key to delete, or false for entire namespace.
	 *
	 * @return bool Result of cache driver delete.
	 */
	public static function clearObjectCache(string $namespace = '', string|false $key = false): bool {
		$cacheDriver = Cache::getInstance();

		return $cacheDriver->delete($namespace, $key);
	}

	/**
	 * Clear the page cache for a given host/path.
	 *
	 * @param string|null $host Hostname.
	 * @param string      $path Relative path.
	 *
	 * @return bool Result of page cache purge.
	 */
	public static function clearPageCache(?string $host = null, string $path = ''): bool {
		$pageCache = new PageCache($host);

		return $pageCache->purge($path);
	}

	/**
	 * Clear all caches (model, object, frontend, compiled, page, opcache).
	 *
	 * @param string $namespace Optional namespace to scope the clear.
	 *
	 * @return bool Always returns true.
	 */
	public static function delete(string $namespace = ''): bool {
		self :: clearModelCache();
		self :: clearObjectCache($namespace);
		self :: clearFrontend();
		self :: clearCompiledFiles($namespace);
		self :: clearPageCache();

		if (function_exists('opcache_reset')) {
			@opcache_reset();
		}

		return true;
	}
}
