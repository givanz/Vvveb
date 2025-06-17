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
use Vvveb\System\Core\FrontController;

class PageCache {
	const STALE_EXT = '.old';

	const LOCK_EXT = '.new';

	const CACHE_DIR = PAGE_CACHE_DIR;

	const MAX_LOCK_SECONDS = 10;

	static $cacheCookies = ['user', 'cart', 'admin', 'nocache'];

	private $fileName;

	private $uri;

	private $canCache; //can serve cached page

	private $canSaveCache; //can save page in cache

	private $cacheFolder;

	static private $enabled;

	static private $instance;

	static function getInstance() {
		if (! self :: $instance) {
			self :: $instance = new self();
		}

		return self :: $instance;
	}

	function __construct($host = null) {
		$this->cacheFolder = $this->cacheFolder($host);
		$this->fileName    = $this->fileName();
		//$this->canSaveCache    = $this->canSaveCache();
	}

	function cacheFolder($host) {
		$host    = $host ?? $_SERVER['HTTP_HOST'] ?? '';
		$hostWp  = substr($host, 0, strpos($host, ':') ?: null);

		return DIR_PUBLIC . self :: CACHE_DIR . ($hostWp ?? 'default');
	}

	function fileName() {
		$uri = $_SERVER['REQUEST_URI'] ?? '/';

		if (strlen($uri) > 300) {
			return false;
		}

		if (substr($uri, -1) == '/') {
			$uri .= 'index.html';
		}

		$this->uri = $uri;

		return $file_cache = $this->cacheFolder . $uri;
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

		if (is_dir($this->fileName)) {
			$this->fileName .= DS . 'index.html';
		}

		if (! is_dir($dir)) {
			//if page with the same name as folder remove
			if (file_exists($dir)) {
				unlink($dir);
			}

			if (! mkdir($dir, (0755 & ~umask()), true)) {
				return false;
			}
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

	function validUrl($url) {
		//no ?&\ or .. in the url and the number of levels should not exceed 4
		$invalid = strpbrk($url, '?&\\') || (strpos($url, '..') !== false) || (substr_count($url,'/') > 4);

		if (! $invalid) {
			foreach (['/user', '/cart', '/checkout', '/feed'] as $a) {
				if (strncmp($url, $a, strlen($a)) === 0) {
					$invalid = true;

					break;
				}
			}
		}

		return ! $invalid;
	}

	function canCache() {
		if ($this->canCache !== NULL) {
			return $this->canCache;
		}

		if (! $this->uri ||
			APP == 'admin' || //no cache for admin
			APP == 'install' || //no cache for install
			! empty($_POST) || //forms posts
			isset($_COOKIE['nocache']) || //cookie set by plugin
			isset($_COOKIE['cart']) || // cookie set by add to cart
			isset($_COOKIE['user']) || //cookie set by login
			isset($_COOKIE['admin']) || //cookie set by admin login
			! $this->validUrl($this->uri) //valid url
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
		//remove lock and stale cache
		foreach ([$this->fileName . self :: LOCK_EXT,
			$this->fileName . self :: STALE_EXT, ] as $file) {
			if (file_exists($file)) {
				return unlink($file);
			}
		}

		return false;
	}

	function saveCache() {
		$data = ob_get_contents();

		//if page not found or server error don't cache
		if (FrontController::getStatus() != 200) {
			//remove lock
			$lock = $this->fileName . self :: LOCK_EXT;

			if (file_exists($lock)) {
				unlink($lock);
			}

			//remove all empty created folders
			while (
			($dir = dirname($this->fileName)) &&
			(strpos($dir, $this->cacheFolder . DS) !== false) && //don't go above site cache folder
			@rmdir($dir) //try to remove empty folder
			) {
				//go one level up
				$this->fileName = $dir;
			}

			return $data;
		}

		if ($this->canSaveCache &&
			$this->fileName && $data &&
			http_response_code() == 200) {
			//create directory structure
			if (is_dir($this->fileName)) {
				$this->fileName .= DS . 'index.html';
			}

			$dir = dirname($this->fileName);

			if (! file_exists($dir)) {
				if (! mkdir($dir, (0755 & ~umask()), true)) {
					return false;
				}
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
		$folder = $this->cacheFolder . $path;
		$glob   = ['', '*/*/', '*/'];

		//$files = glob($name, GLOB_BRACE);
		$files = globBrace($folder, $glob, '*');

		if ($files) {
			foreach ($files as $file) {
				if ($file[0] === '.') {
					continue;
				}

				if (is_file($file)) {
					if (! @unlink($file)) {
						clearstatcache(false, $file);
					}
				} else {
					/*
					if (! @rmdir($file)) {
						clearstatcache(false, $file);
					}
					*/
				}
			}
		}

		return true;
	}

	static function enable($type = false) {
		$cookie = (in_array($type, self :: $cacheCookies) ? $type : 'nocache');

		if (! self :: $enabled && isset($_COOKIE[$cookie])) {
			setcookie($cookie, '', time() - 3600, '/');
		}

		self :: $enabled = true;
	}

	static function disable($type = false) {
		$cookie = (in_array($type, self :: $cacheCookies) ? $type : 'nocache');

		if (self :: $enabled && ! isset($_COOKIE[$cookie])) {
			setcookie($cookie, '1', 0, '/');
		}

		self :: $enabled = false;
	}
}
