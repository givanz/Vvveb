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
use function Vvveb\download;
use function Vvveb\getUrl;
use Vvveb\System\Event;
use Vvveb\System\Functions\Str;
use Vvveb\System\Import\Rss;

abstract class Extensions {
	/**
	 * @var array<array-key, mixed>
	 */
	protected static array $extensions = [];

	/**
	 * @var array<array-key, mixed>
	 */
	protected static array $categories = [];

	/**
	 * @var string
	 */
	protected static string $extension = 'extension';

	/**
	 * @var string
	 */
	protected static string $baseDir = 'extension';

	/**
	 * @var string
	 */
	protected static string $url = '';

	/**
	 * @var string
	 */
	protected static string $feedUrl = '';

	/**
	 * @var string
	 */
	protected static string $categoriesFeedUrl = '';

	public const KEY_VALUE_REGEX = '/^([\w -]+):\s+(.+?)\s*$/m';

	/**
	 * @param string $comments
	 *
	 * @return array<array-key, mixed>
	 */
	public static function getParams(string $comments): array {
		$results = [];

		if (preg_match_all(static::KEY_VALUE_REGEX, $comments, $matches)) {
			$matches[1] = array_map(fn ($key) => str_replace(' ', '-', strtolower(trim($key))), $matches[1]);

			$matches[2] = array_map('trim', $matches[2]);

			$results = array_combine($matches[1], $matches[2]);
		}

		return $results;
	}

	/**
	 * @param string $content
	 *
	 * @return string
	 */
	public static function getComments(string $content): string {
		//extract block comments; line comments are not used for extension info headers
		if (preg_match_all('@(?s)/\*.*?\*/@', $content, $matches, PREG_PATTERN_ORDER)) {
			return implode("\n", $matches[0]);
		}

		return '';
	}

	/**
	 * @param string       $content
	 * @param string|false $name
	 *
	 * @return array<array-key, mixed>
	 */
	public static function getInfo(string $content, string|false $name = false): array {
		$comments = static::getComments($content);
		$params   = static::getParams($comments);

		if (isset($params['status'])) {
			unset($params['status']);
		}

		if (isset($params['category']) && $name) {
			static::$categories[$params['category']][] = $name;
		}

		return $params;
	}

	/**
	 * @param string $path
	 *
	 * @return array<array-key, mixed>
	 */
	public static function getListInfo(string $path): array {
		if (isset(static::$extensions[static::$extension])) {
			return static::$extensions[static::$extension];
		} else {
			static::$extensions[static::$extension] = [];
		}

		$adminPath = \Vvveb\adminPath();
		$list    	 = glob($path);

		foreach ($list as $file) {
			$content		      = file_get_contents($file);
			$dir			         = Str::match('@(.+)[\\/][a-z]+.\w+$@', $file);
			$folder		       = Str::match('@[\\/]([^\\/]+)[\\/][a-z]+.\w+$@', $file);
			$info           = static::getInfo($content, $folder);
			$info['file']   = $file;
			$info['folder'] = $folder;
			$info['import'] = file_exists($dir . DS . 'import');

			if (isset($info['settings'])) {
				$info['settings'] = str_replace('/admin/', $adminPath, $info['settings']);
			}

			// plugin folder does not match slug
			if (!isset($info['slug']) || ($info['slug'] !== $info['folder'])) {
				$info['status'] = 'slug_folder_mismatch';
				$info['slug']   = $info['folder'];
			}

			static::$extensions[static::$extension][$folder] = $info;
		}

		return static::$extensions[static::$extension];
	}

	/**
	 * @return array<array-key, mixed>
	 */
	public static function getCategories(): array {
		return static::$categories;
	}

	/**
	 * @param string $url
	 *
	 * @return string|false
	 */
	public static function download(string $url): string|false {
		$temp = @tempnam(sys_get_temp_dir(), 'vvveb_plugin');

		if (!$temp && ($handle = @tmpfile())) {
			//tmpfile() returns a stream, not a path: resolve it via metadata.
			//fclose() deletes the file, the path is recreated by file_put_contents below.
			$meta = stream_get_meta_data($handle);
			$temp = $meta['uri'] ?? false;
			fclose($handle);
		}

		//if temp still fails use cache dir, some shared hosting have this issue
		if (!$temp) {
			$temp = DIR_CACHE . APP . '.extension.' . md5($url) . '.zip';
		}

		if (($content = download($url)) && @file_put_contents($temp, $content, LOCK_EX) !== false) {
			return $temp;
		}

		return false;
	}

	/**
	 * @param string $file
	 *
	 * @return bool
	 */
	public static function validFile(string $file): bool {
		if (str_contains($file, '..')) {
			return false;
		}

		if (str_contains($file, 'htaccess')) {
			return false;
		}

		//for public folder don't allow php and htaccess files
		if (str_contains($file, 'public')) {
			$extension = strtolower(substr($file, strrpos($file, '.') + 1));
			if (in_array($extension, ['php', 'htaccess'])) {
				return false;
			}
		}

		return true;
	}

	/**
	 * @param string       $extensionZipFile
	 * @param string|false $slug
	 * @param bool         $validate
	 *
	 * @return string|bool
	 */
	public static function install(string $extensionZipFile, string|false $slug = false, bool $validate = true): string|bool {
		$extension   = static::$extension;
		$success     = true;
		$extractTo   = static::$baseDir;
		$fileCheck   = "$extension.php";
		$valid       = false;

		$zip = new \ZipArchive();

		if ($zip->open($extensionZipFile/*, \ZipArchive::OVERWRITE*/) === true) {
			if ($zip->numFiles === 0) {
				throw new \Exception(__('File is not a valid zip archive!'));
			}

			$info       = false;
			$folderName = $zip->getNameIndex(0);

			//check if first entry is a directory
			if (substr($folderName, -1, 1) !== '/') {
				if ($validate) {
					throw new \Exception(sprintf('%s zip must have only %s folder!', $extensionZipFile, ucfirst($extension)));
				}
			} else {
				$slug = trim($folderName, '/');
			}

			for ($i = $zip->numFiles - 1; ($i > 0 && $success === true); $i--) {
				$file = $zip->getNameIndex($i);

				if (!self::validFile($file)) {
					throw new \Exception(sprintf(__('Zip contains invalid file "%s"'), $file));
				}

				if ($validate) {
					//check if all files inside the extension folder
					if (!str_contains($file, $folderName)) {
						$extractTo .= DS . $slug;

						throw new \Exception(sprintf(__('%s zip should not have other files than %s folder!'), $extension, $extension));
					}
				}

				// plugin.php must be in the top most folder
				if (str_contains($file, $fileCheck) && substr_count($file, '/') < 2) {
					$content = $zip->getFromName($file);
					$info    = static::getInfo($content);

					if (isset($info['slug']) && ($folderName == ($info['slug'] . '/'))) {
						// Unzip Path
						$success = $info['slug'];
						$valid   = true;
					} else {
						if ($validate) {
							throw new \Exception(sprintf(__('%s slug `%s` does not match folder %s!'), $extension, $info['slug'] ?? '', $folderName));
						} else {
							$extractTo .= DS . $slug;
							$info    = ['slug' => $slug];
							$success = $info['slug'];
						}
					}
				}
			}

			// no extension.php found then treat as generic extension/theme
			if ($success && $valid) {
				if (!is_writable($extractTo)) {
					throw new \Exception(sprintf(__('%s folder not writable!'), str_replace(DIR_ROOT, '', $extractTo)));
				}

				if ($zip->extractTo($extractTo)) {
					$success = $slug;
					$info    = ['slug' => $slug];
				} else {
					$success = false;
				}
			}

			$zip->close();

			if (!$info) {
				throw new \Exception(sprintf(__('No `%s.php` info found inside zip!'), $extension));
			}
		} else {
			throw new \Exception(__('File is not a valid zip archive!'));
		}

		Event::trigger(__CLASS__, __FUNCTION__, $extensionZipFile, $success);

		return $success;
	}

	/**
	 * @return ?string
	 */
	public static function marketUrl(): ?string {
		return static::$url;
	}

	/**
	 * @param string                  $url
	 * @param array<array-key, mixed> $params
	 * @param ?string                 $key
	 *
	 * @return array<array-key, mixed>
	 */
	public static function getMarketUrl(string $url, array $params = [], ?string $key = null): array {
		$query            = http_build_query($params);
		$content          = getUrl($url . '?' . $query);

		if ($content) {
			$rss  = new Rss($content);

			$key ??= static::$extension . 's';
			$result[$key]                       = $rss->get($params['start'] ?? 1, $params['limit'] ?? 10);
			$result['count']                    = $rss->value('count');

			return $result;
		}

		return [];
	}

	/**
	 * @param array<array-key, mixed> $params
	 * @param ?string                 $key
	 *
	 * @return array<array-key, mixed>
	 */
	public static function getMarketCategories(array $params = [], ?string $key = null): array {
		return static::getMarketUrl(static::$categoriesFeedUrl, $params, $key);
	}

	/**
	 * @param array<array-key, mixed> $params
	 * @param ?string                 $key
	 *
	 * @return array<array-key, mixed>
	 */
	public static function getMarketList(array $params = [], ?string $key = null): array {
		return static::getMarketUrl(static::$feedUrl, $params, $key);
	}
}
