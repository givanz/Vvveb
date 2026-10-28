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

use function Vvveb\rrmdir;
use Vvveb\System\Core\FrontController;

/**
 * Full-page static cache.
 *
 * Serves responses from flat HTML files keyed by request URI, with a
 * lock/stale protocol so visitors get fresh-enough content while pages
 * regenerate: existing cache is renamed to ".old" (served while locked),
 * generation is guarded by a ".new" lock file, and non-200 responses
 * discard both instead of caching errors.
 */
class PageCache {
	public const STALE_EXT = '.old';

	public const LOCK_EXT = '.new';

	public const CACHE_DIR = PAGE_CACHE_DIR;

	public const MAX_LOCK_SECONDS = 10;

	/** @var string File name used for directory-style URIs. */
	public const INDEX_FILE = 'index.html';

	/** @var int Maximum URI length eligible for caching. */
	public const MAX_URI_LENGTH = 300;

	/** @var int Base directory permissions (further restricted by umask). */
	public const DIR_MODE = 0755;

	/** @var string[] URI prefixes that are never cached. */
	public const BLOCKED_PATHS = ['/user', '/cart', '/checkout', '/feed'];

	/** @var string[] Cookie names that disable caching. */
	public static array $cacheCookies = ['user', 'cart', 'admin', 'nocache'];

	/** @var ?string Full path to the cache file. */
	private ?string $fileName = null;

	/** @var ?string Request URI being cached. */
	private ?string $uri = null;

	/** @var ?bool Whether the current request may be served from cache. */
	private ?bool $canCache = null;

	/** @var ?bool Whether the current response may be saved to cache. */
	private ?bool $canSaveCache = null;

	/** @var string Absolute path to the host-specific cache folder. */
	private string $cacheFolder = '';

	/** @var bool Whether caching is globally enabled. */
	private static bool $enabled = true;

	/** @var ?self Singleton instance. */
	private static ?self $instance = null;

	/**
	 * Return the singleton PageCache instance.
	 *
	 * @return self
	 */
	public static function getInstance(): self {
		if (!self::$instance) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * @param ?string $host Optional hostname override.
	 */
	public function __construct(?string $host = null) {
		$this->cacheFolder = $this->cacheFolder($host);
		$this->uri         = $this->uri();
		if ($this->uri) {
			$this->fileName = $this->cacheFolder . $this->uri;
		}
	}

	/**
	 * Build the absolute cache folder path for the given host.
	 *
	 * @param ?string $host Hostname, or null to use $_SERVER['HTTP_HOST'].
	 *
	 * @return string
	 */
	public function cacheFolder(?string $host = null): string {
		$host ??= $_SERVER['HTTP_HOST'] ?? '';

		//strip the port component (e.g. "example.com:8080" -> "example.com")
		$pos    = strpos($host, ':');
		$hostWp = $pos === false ? $host : substr($host, 0, $pos);

		//fall back to "default" when no host is available (e.g. CLI)
		$hostWp = $hostWp ?: 'default';

		return DIR_PUBLIC . self::CACHE_DIR . $hostWp;
	}

	/**
	 * Normalise and validate a request URI for caching.
	 *
	 * @param ?string $url URI to validate, or null to use $_SERVER['REQUEST_URI'].
	 *
	 * @return string|false The normalised URI, or false if the URI is too long
	 *                      or contains control characters.
	 */
	public function uri(?string $url = null): string|false {
		$uri = $url ?? $_SERVER['REQUEST_URI'] ?? '/';

		if (strlen($uri) > self::MAX_URI_LENGTH) {
			return false;
		}

		//reject control characters and null bytes - they have no place in a path
		if (preg_match('/[\x00-\x1f\x7f]/', $uri)) {
			return false;
		}

		if (substr($uri, -1) == '/') {
			$uri .= self::INDEX_FILE;
		}

		return $uri;
	}

	/**
	 * Path of the lock file marking an in-progress generation.
	 *
	 * @return string
	 */
	private function lockFile(): string {
		return $this->fileName . self::LOCK_EXT;
	}

	/**
	 * Path of the stale (previous-generation) cache file.
	 *
	 * @return string
	 */
	private function staleFile(): string {
		return $this->fileName . self::STALE_EXT;
	}

	/**
	 * Append the index file name when the cached path is a directory.
	 *
	 * Mutates $this->fileName so subsequent calls in the same request
	 * operate on the resolved path.
	 *
	 * @return string The resolved cache file path.
	 */
	private function resolveIndexFileName(): string {
		if (is_dir($this->fileName)) {
			$this->fileName .= DS . self::INDEX_FILE;
		}

		return $this->fileName;
	}

	/**
	 * Create a directory (and parents), removing a colliding cached page
	 * file that blocks the directory path.
	 *
	 * @param string $dir Directory to create.
	 *
	 * @return bool True on success.
	 */
	private function ensureDirectory(string $dir): bool {
		if (is_dir($dir)) {
			return true;
		}

		//if a page with the same name as the folder exists remove it
		if (file_exists($dir)) {
			unlink($dir);
		}

		return (bool)@mkdir($dir, (self::DIR_MODE & ~umask()), true);
	}

	/**
	 * Check whether a cache file is currently being generated (lock exists).
	 *
	 * @return bool
	 */
	public function isGenerating(): bool {
		return is_file($this->lockFile());
	}

	/**
	 * Check whether the generation lock has exceeded MAX_LOCK_SECONDS.
	 *
	 * @return bool False when no lock exists (generation finished or never started).
	 */
	public function isGeneratingStuck(): bool {
		$lock = $this->lockFile();

		//no lock file means generation already finished (or never started)
		if (!is_file($lock)) {
			return false;
		}

		return time() - (filemtime($lock) ?: time()) > self::MAX_LOCK_SECONDS;
	}

	/**
	 * Check whether a stale (old) cache file exists.
	 *
	 * @return bool
	 */
	public function isStale(): bool {
		return is_file($this->staleFile());
	}

	/**
	 * Return the path to the stale cache file.
	 *
	 * @return string
	 */
	public function getStale(): string {
		return $this->staleFile();
	}

	/**
	 * Prepare directories and lock file for a new cache generation.
	 *
	 * Moves any existing cache file to the stale slot so it can be served
	 * while the new version generates.
	 *
	 * @return bool True on success, false if directory creation fails.
	 */
	public function startGenerating(): bool {
		$dir = dirname($this->fileName);

		$this->resolveIndexFileName();

		if (!$this->ensureDirectory($dir)) {
			return false;
		}

		//keep old cache to serve while generating
		if (file_exists($this->fileName)) {
			rename($this->fileName, $this->staleFile());
		}

		return touch($this->lockFile());
	}

	/**
	 * Start output buffering for cache capture.
	 *
	 * @return void
	 */
	public function startCapture(): void {
		ob_start();
	}

	/**
	 * Validate that a URL is safe for caching.
	 *
	 * @param string $url The URL to validate.
	 *
	 * @return bool True if the URL is valid for caching.
	 */
	public function validUrl(string $url): bool {
		//no ?&\ or .. in the url and the number of levels should not exceed 4
		$invalid = strpbrk($url, '?&\\') || (str_contains($url, '..')) || (substr_count($url, '/') > 4);

		if (!$invalid) {
			foreach (self::BLOCKED_PATHS as $a) {
				if (strncmp($url, $a, strlen($a)) === 0) {
					$invalid = true;

					break;
				}
			}
		}

		return !$invalid;
	}

	/**
	 * Determine whether the current request may be served from cache.
	 *
	 * @return bool
	 */
	public function canCache(): bool {
		if ($this->canCache !== null) {
			return $this->canCache;
		}

		if (self::$enabled === false) {
			return $this->canCache = false;
		}

		if (!$this->uri ||
			APP == 'admin' || //no cache for admin
			APP == 'install' || //no cache for install
			!empty($_POST) || //forms posts
			(isset($_COOKIE['nocache'])) || //cookie set by plugin
			isset($_COOKIE['cart']) || // cookie set by add to cart
			isset($_COOKIE['user']) || //cookie set by login
			isset($_COOKIE['admin']) || //cookie set by admin login
			!$this->validUrl($this->uri) //valid url
		) {
			return $this->canCache = false;
		}

		return $this->canCache = true;
	}

	/**
	 * Determine whether the current response may be saved to cache.
	 *
	 * Only anonymous visitors' responses are cached.
	 *
	 * @return bool
	 */
	public function canSaveCache(): bool {
		if ($this->canSaveCache !== null) {
			return $this->canSaveCache;
		}

		if (!$this->canCache ||
			($user = \Vvveb\System\User\User::current()) || //not logged in
			($admin = \Vvveb\System\User\Admin::current())) {
			return $this->canSaveCache = false;
		}

		return $this->canSaveCache = true;
	}

	/**
	 * Check whether a cache file exists for the current request.
	 *
	 * @return bool
	 */
	public function hasCache(): bool {
		return is_file($this->fileName);
	}

	/**
	 * Output the cached file contents directly.
	 *
	 * @return int|false Number of bytes read (from readfile), or false if the
	 *                   cache file disappeared between hasCache() and this call.
	 */
	public function getCache(): int|false {
		if (!is_file($this->fileName)) {
			return false;
		}

		return readfile($this->fileName);
	}

	/**
	 * Remove lock and stale cache files.
	 *
	 * @return bool True if any file was removed, false otherwise.
	 */
	public function cleanUp(): bool {
		$removed = false;

		foreach ([$this->lockFile(), $this->staleFile()] as $file) {
			if (file_exists($file)) {
				$removed = @unlink($file) || $removed;
			}
		}

		return $removed;
	}

	/**
	 * Save the current output buffer to the cache file.
	 *
	 * Non-200 responses discard the lock and stale files (a temporary error
	 * must not evict the last good cache) and prune the empty folders that
	 * were created for them.
	 *
	 * @return string|false The captured output, or false on directory-creation failure.
	 */
	public function saveCache(): string|false {
		$data = ob_get_contents();

		$lock = $this->lockFile();

		//if page not found or server error don't cache
		if (FrontController::getStatus() != 200) {
			//remove lock
			if (file_exists($lock)) {
				unlink($lock);
			}

			//remove stale cache so a temporary error doesn't leave orphaned files
			if (file_exists($this->staleFile())) {
				@unlink($this->staleFile());
			}

			//remove all empty created folders
			$file = $this->fileName;

			while (
				($dir = dirname($file)) &&
				(str_contains($dir, $this->cacheFolder . DS)) && //don't go above site cache folder
				@rmdir($dir) //try to remove empty folder
			) {
				//go one level up
				$file = $dir;
			}

			return $data;
		}

		if ($this->canSaveCache &&
			$this->fileName && $data &&
			http_response_code() == 200) { //status 200 already ensured above
			$this->resolveIndexFileName();

			if (!$this->ensureDirectory(dirname($this->fileName))) {
				return false;
			}

			//save cache
			file_put_contents($this->fileName, $data);
			//remove lock
			if (file_exists($lock)) {
				unlink($lock);
			}

			//remove stale
			if (file_exists($this->staleFile())) {
				@unlink($this->staleFile());
			}
		}

		return $data;
	}

	/**
	 * Delete all cached files under the given path.
	 *
	 * @param string $path Relative path inside the cache folder.
	 *
	 * @return bool Always returns true.
	 */
	public function purge(string $path = '/'): bool {
		//reject paths that would escape the site cache folder
		if (str_contains($path, '..') || strpbrk($path, "\0")) {
			return false;
		}

		$folder = $this->cacheFolder . $path;

		return rrmdir($folder, [], true);
	}

	/**
	 * Remove all expired cache files from the cache directory.
	 *
	 * A file is considered expired when its modification time is older than
	 * $expired seconds. Lock (.new) and stale (.old) files are removed as well
	 * once past their expiry.
	 *
	 * @param int $expired Expiry window in seconds.
	 *
	 * @return void
	 */
	public function purgeExpired($expired = 1000): void {
		ignore_user_abort(true);
		rrmdir($this->cacheFolder, [], true, function($file) use ($expired): bool {
			$time = filemtime($file);

			return ($time + $expired) < time();
		});
		ignore_user_abort(false);
	}

	/**
	 * Randomly trigger expired-cache cleanup on object destruction.
	 *
	 * There is a 1-in-1000 chance that purgeExpired() is called each time
	 * a File cache instance is destroyed, providing probabilistic garbage
	 * collection without requiring a cron job.
	 *
	 * @return void
	 */
	public function __destruct() {
		if (random_int(1, 1000) === 1) {
			$this->purgeExpired();
		}
	}

	/**
	 * Enable caching, optionally for a specific cookie type.
	 *
	 * @param string|false $type Cookie name that controls caching, or false for default.
	 *
	 * @return void
	 */
	public static function enable(string|false $type = false): void {
		$cookie = (in_array($type, self::$cacheCookies) ? $type : 'nocache');

		if (!self::$enabled && isset($_COOKIE[$cookie])) {
			@setcookie($cookie, '', time() - 3600, '/');
		}

		self::$enabled = true;
	}

	/**
	 * Disable caching, optionally for a specific cookie type.
	 *
	 * @param string|false $type Cookie name that controls caching, or false for default.
	 *
	 * @return void
	 */
	public static function disable(string|false $type = false): void {
		$cookie = (in_array($type, self::$cacheCookies) ? $type : 'nocache');

		if (self::$enabled && !isset($_COOKIE[$cookie])) {
			@setcookie($cookie, '1', 0, '/');
		}

		self::$enabled = false;
	}
}
