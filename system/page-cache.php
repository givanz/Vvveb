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

use Vvveb\System\Core\FrontController;

class PageCache {
	const STALE_EXT = '.old';

	const LOCK_EXT = '.new';

	const CACHE_DIR = PAGE_CACHE_DIR;

	const MAX_LOCK_SECONDS = 60;

	private $fileName;

	private $canCache; //can serve cached page

	private $canSaveCache; //can save page in cache

	private $cacheFolder;

	static private $instance;

	static function getInstance() {
		if (! self :: $instance) {
			self :: $instance = new self();
		}

		return self :: $instance;
	}

	function __construct() {
		$this->cacheFolder = $this->cacheFolder();
		$this->fileName    = $this->fileName();
		//$this->canSaveCache    = $this->canSaveCache();
	}

	function cacheFolder() {
		return DIR_PUBLIC . self :: CACHE_DIR . ($_SERVER['HTTP_HOST'] ?? 'default');
	}

	function fileName() {
		$path = $_SERVER['REQUEST_URI'] ?? '/';

		if (strlen($path) > 300) {
			return false;
		}

		if (substr($path, -1) == '/') {
			$path .= 'index.html';
		}

		return $file_cache = $this->cacheFolder . $path;
	}

	function isGenerating() {
		return is_file($this->fileName . self :: LOCK_EXT);
	}

	function isGeneratingStuck() {
		return time() - filemtime($this->fileName . self :: LOCK_EXT) > self :: MAX_LOCK_SECONDS;
	}

	function isStale() {
		return is_file($this->fileName . self :: STALE_EXT);
	}

	function getStale() {
		return $this->fileName . self :: STALE_EXT;
	}

	function startGenerating() {
		$dir = dirname($this->fileName);

		if (! file_exists($dir)) {
			mkdir($dir, (0755 & ~umask()), true);
		}

		//keep old cache to serve while generating
		if (file_exists($this->fileName)) {
			rename($this->fileName, $this->fileName . self :: STALE_EXT);
		}

		return touch($this->fileName . self :: LOCK_EXT);
	}

	function startCapture() {
		ob_start();
	}

	function canCache() {
		if ($this->canCache !== NULL) {
			return $this->canCache;
		}

		if (! $this->fileName ||
			APP == 'admin' || //no cache for admin
			APP == 'install' || //no cache for install
			! empty($_POST) || //forms posts
			isset($_COOKIE['nocache']) || //cookie set by plugin
			isset($_COOKIE['cart']) || // cookie set by add to cart
			isset($_COOKIE['user']) || //cookie set by login
			strpbrk($this->fileName, '?&') //valid url
			) {
			return $this->canCache = false;
		}

		return $this->canCache = true;
	}

	function canSaveCache() {
		if ($this->canSaveCache !== NULL) {
			return $this->canSaveCache;
		}

		if (! $this->canCache ||
			($user = \Vvveb\System\User\User::current()) || //not logged in
			($admin = \Vvveb\System\User\Admin::current())) {
			return $this->canSaveCache = false;
		}

		return $this->canSaveCache = true;
	}

	function hasCache() {
		return is_file($this->fileName);
	}

	function getCache() {
		return readfile($this->fileName);
	}

	function cleanUp() {
		//remove lock
		$file = $this->fileName . self :: LOCK_EXT;

		if (file_exists($file)) {
			return unlink($file);
		}

		return false;
	}

	function saveCache() {
		$data = ob_get_contents();

		//if page not found or server error don't cache
		if (FrontController::getStatus() != 200) {
			//remove lock
			unlink($this->fileName . self :: LOCK_EXT);

			return $data;
		}

		if ($this->canSaveCache &&
			$this->fileName && $data &&
			http_response_code() == 200) {
			//create directory structure
			$dir = dirname($this->fileName);

			if (! file_exists($dir)) {
				mkdir($dir, (0755 & ~umask()), true);
			}
			//save cache
			file_put_contents($this->fileName, $data);
			//remove lock
			unlink($this->fileName . self :: LOCK_EXT);
			//remove stale
			if (file_exists($this->fileName . self :: STALE_EXT)) {
				@unlink($this->fileName . self :: STALE_EXT);
			}
		}

		return $data;
	}

	function purge($path = '/') {
		$name = $this->cacheFolder . $path;
		$name .= '{,*/*/,*/}*';

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

	static function enable() {
		setcookie('nocache', '', time() - 3600, '/');
	}

	static function disable() {
		setcookie('nocache', '1', 0, '/');
	}
}
