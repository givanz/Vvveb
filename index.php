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

use Vvveb\System\Core\FrontController;
use Vvveb\System\PageCache;

define('V_VERSION', '0.0.7');

define('DS', DIRECTORY_SEPARATOR);
defined('DIR_ROOT') || define('DIR_ROOT', __DIR__ . DS);
defined('DIR_CONFIG') || define('DIR_CONFIG', DIR_ROOT . 'config' . DS);
defined('DIR_SYSTEM') || define('DIR_SYSTEM', DIR_ROOT . 'system' . DS);
defined('PAGE_CACHE_DIR') || define('PAGE_CACHE_DIR', 'page-cache' . DS);
//common constants
include DIR_ROOT . 'env.php';

function is_installed() {
	return file_exists(DIR_ROOT . 'config' . DS . 'db.php');
}

if (! defined('APP')) {
	if (is_installed()) {
		define('APP', 'app');
	} else {
		define('APP', 'install');

		if (isset($_SERVER['REQUEST_URI']) && strpos($_SERVER['REQUEST_URI'], 'install') === false) {
			//avoid redirect loop
			die(header('Location: /install'));
		}
	}
} elseif (! is_installed() && APP != 'install') {
	define('APP', 'install');

	if (isset($_SERVER['REQUEST_URI']) && strpos($_SERVER['REQUEST_URI'], 'install') === false) {
		die(header('Location: /install'));
	}
}

if (! isset($PUBLIC_PATH)) {
	$PUBLIC_PATH = '/public/';
}

if (! isset($PUBLIC_THEME_PATH)) {
	$PUBLIC_THEME_PATH = '/public/';
}

if (! defined('PUBLIC_PATH')) {
	define('PUBLIC_PATH', (V_SUBDIR_INSTALL ? V_SUBDIR_INSTALL : '') . $PUBLIC_PATH);
	define('PUBLIC_THEME_PATH', (V_SUBDIR_INSTALL ? V_SUBDIR_INSTALL : '') . $PUBLIC_THEME_PATH);
}

require_once DIR_SYSTEM . 'core/startup.php';

if (PAGE_CACHE) {
	require_once DIR_SYSTEM . 'page-cache.php';
	$pageCache   = PageCache::getInstance();
	$waitSeconds = 10;

	function saveCache() {
		$pageCache = PageCache::getInstance();
		
		if ($pageCache->canSaveCache()) {
			$pageCache->startGenerating();
			$pageCache->startCapture();

			System\Core\start();

			return $pageCache->saveCache();
		} else {
			System\Core\start();
		}
	}

	if ($pageCache->canCache()) {
		if ($pageCache->hasCache()) {
			return $pageCache->getCache();
		} else {
			if ($pageCache->isStale()) {
				if ($pageCache->isGenerating()) {
					return $pageCache->getStale();
				} else {
					return saveCache();
				}
			} else {
				//if cache is already generating
				//wait 10 seconds for cache generation
				//if it takes longer then give up
				$i = 0;

				while ($pageCache->isGenerating() && $i++ <= $waitSeconds) {
					sleep(1);

					if ($pageCache->hasCache()) {
						return $pageCache->getCache();
					}
				}

				//if page took longer than 10 seconds
				//check if the generating page is older than 1 minute
				//if cache is older than 1 minute then regenerate
				//if is not older than 1 minute then show maintenance server overloaded page
				if ($i >= $waitSeconds) {
					if (! $pageCache->isGeneratingStuck()) {
						define('SITE_ID', 1);

						return FrontController::notFound(false, __('Server overload!'), 500);
					}
				}

				return saveCache();
			}
		}
	} else {
		System\Core\start();
	}
} else {
	System\Core\start();
}
