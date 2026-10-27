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

namespace Vvveb\System\Traits;

use function Vvveb\__;
use function Vvveb\fileUploadErrMessage;
use function Vvveb\humanReadable;
use function Vvveb\parseQuantity;
use function Vvveb\rrmdir;
use function Vvveb\sanitizeFileName;
use Vvveb\System\Event;
use Vvveb\System\User\Admin;

trait Media {
	/**
	 * File extensions that are not allowed for upload.
	 *
	 * @var list<string>
	 */
	public array $uploadDenyExtensions = ['php', 'tpl', 'svg', 'js', 'exe', 'html', 'phtml', 'htaccess', 'phar'];

	/**
	 * MIME types that are not allowed for upload.
	 *
	 * @var list<string>
	 */
	public array $uploadDenyMime = ['image/svg', 'image/svg+xml', 'application/javascript', 'application/x-msdownload'];

	/**
	 * MIME types whose EXIF/metadata will be stripped on upload.
	 *
	 * @var list<string>
	 */
	public array $stripMetadataMime = ['image/jpg', 'image/jpeg', 'image/png', 'image/webp', 'image/avif'];

	/**
	 * Return the filesystem path for a given directory type.
	 *
	 * @param string $type One of 'public', 'plugins', or 'themes'.
	 *
	 * @return string|false The directory path, or false if the type is invalid.
	 */
	protected function dirForType(string $type): string|false {
		return match ($type) {
			'public'  => DIR_MEDIA,
			'plugins' => DIR_PLUGINS,
			'themes'  => DIR_THEMES,
			default   => false,
		};
	}

	/**
	 * Set the media-related URLs and upload limits on the view.
	 *
	 * @param string $controllerPath Base controller URL path.
	 * @param string $params         Additional URL query parameters.
	 *
	 * @return void
	 */
	protected function setMediaEndpoints(string $controllerPath, string $params = ''): void {
		$this->view->mediaUrl          = $controllerPath;
		$this->view->scanUrl           = "$controllerPath&action=scan$params";
		$this->view->uploadUrl         = "$controllerPath&action=upload$params";
		$this->view->deleteUrl         = "$controllerPath&action=delete$params";
		$this->view->renameUrl         = "$controllerPath&action=rename$params";
		$this->view->uploadMaxFilesize = parseQuantity(ini_get('upload_max_filesize'));
		$this->view->postMaxSize       = parseQuantity(ini_get('post_max_size'));
	}


	/**
	 * Create a media library database record for a stored file.
	 *
	 * @param string      $file Media-relative file path as returned by storeMediaFile().
	 * @param string|null $type MIME type, detected from the stored file when omitted.
	 *
	 * @return mixed Model result, the existing record, or null when skipped/failed.
	 */
	protected function createMediaRecord(string $file, ?string $type = null): mixed {
		if ($file === '') {
			return null;
		}

		try {
			$global = (isset($this->global) && is_array($this->global)) ? $this->global : [];
			$model  = \Vvveb\model('media_content');

			$existing = $model->get(['file' => $file] + $global);

			if ($existing) {
				return $existing;
			}

			$base = pathinfo(str_replace('\\', '/', $file), PATHINFO_FILENAME);
			$name = humanReadable($base);

			$languageId = $global['language_id'] ?? $global['default_language_id'] ?? 1;

			if ($type === null && isset($this->dirMedia)) {
				$absolute = $this->dirMedia . DS . $file;

				if (is_file($absolute)) {
					$detected = @mime_content_type($absolute);

					if (is_string($detected) && $detected !== '') {
						$type = $detected;
					}
				}
			}

			$media = ['file' => trim($file, " /\n\r\t\v\x00" . DS)];

			if ($type !== null && $type !== '') {
				$media['type'] = $type;
			}

			return $model->add([
				'media'         => $media,
				'media_content' => [['name' => $name, 'language_id' => $languageId]],
			]);
		} catch (\Throwable $e) {
			return null;
		}
	}

	/**
	 * Store one file in the media library.
	 *
	 * @param string                                                                                   $srcPath  Readable temp file with the bytes.
	 * @param string                                                                                   $fileName Desired file name (sanitized here).
	 * @param string                                                                                   $path     Media sub-path, may be empty.
	 * @param array{overwrite?: bool, onlyFilename?: bool, size?: int, error?: int, httpUpload?: bool} $opts     Upload error code (UPLOAD_ERR_OK), overwrite and return-shape flags.
	 *
	 * @return array{success: bool, message: string, file: string, size: int} Per-file result.
	 */
	protected function storeMediaFile(string $srcPath, string $fileName, string $path = '', array $opts = []): array {
		$overwrite    = $opts['overwrite'] ?? false;
		$onlyFilename = $opts['onlyFilename'] ?? false;
		$size         = $opts['size'] ?? (@filesize($srcPath) ?: 0);
		$error        = $opts['error'] ?? UPLOAD_ERR_OK;
		$httpUpload   = $opts['httpUpload'] ?? false;
		$success      = false;
		$return       = $fileName;
		$message      = '';

		$path     = sanitizeFileName($path);
		$fileName = sanitizeFileName($fileName);

		if (!$fileName || !is_file($srcPath)) {
			return ['success' => false, 'message' => __('Invalid upload!'), 'file' => $fileName, 'size' => 0];
		}

		if (V_SUBDIR_INSTALL && str_starts_with($path, V_SUBDIR_INSTALL)) {
			$path = substr_replace($path, '', 0, strlen(V_SUBDIR_INSTALL));
		}

		$path      = preg_replace('@.*[\\\/]public[\\\/]media|.*[\\\/]media|.*[\\\/]public@', '', $path);
		$extension = strtolower(substr($fileName, strrpos($fileName, '.') + 1));
		$mimeType  = mime_content_type($srcPath);

		if ($error != UPLOAD_ERR_OK) {
			return ['success' => $success, 'message' => fileUploadErrMessage($error), 'file' => $fileName, 'size' => $size];
		}

		if (in_array($extension, $this->uploadDenyExtensions)) {
			return ['success' => false, 'message' => sprintf(__('File type %s not allowed!'), $extension), 'file' => $fileName, 'size' => $size];
		}

		if (in_array($mimeType, $this->uploadDenyMime)) {
			return ['success' => false, 'message' => sprintf(__('File type %s not allowed!'), $mimeType), 'file' => $fileName, 'size' => $size];
		}

		if (in_array($mimeType, $this->stripMetadataMime)) {
			//metadata stripping should be handled by the caller
		}

		//deny hidden files
		if ($fileName[0] === '.') {
			return ['success' => false, 'message' => __('Invalid upload!'), 'file' => $fileName, 'size' => $size];
		}

		//check if the path is inside the allowed path
		/** @var string|false $destination */
		$destination = realpath($this->dirMedia . DS . $path . DS);

		//ensure the media dir ends with a separator so that the prefix
		//check cannot be bypassed by sibling directories that share the
		//same name prefix (e.g. /var/www/media-evil vs /var/www/media)
		$mediaDir    = rtrim($this->dirMedia, DS) . DS;
		$destination = $destination === false ? false : rtrim($destination, DS) . DS;

		if ($destination === false || !str_starts_with($destination, $mediaDir)) {
			return ['success' => false, 'message' => __('Invalid upload!'), 'file' => $fileName, 'size' => $size];
		}

		$origFilename = $fileName;
		$i            = 1;
		$destination .= DS . $fileName;

		if (!$overwrite) {
			while (file_exists($destination = $this->dirMedia . $path . DS . $fileName) && ($i++ < 5)) {
				$fileName = random_int(0, 10000) . '-' . $origFilename;
			}
		}

		$moved = $httpUpload ? @move_uploaded_file($srcPath, $destination) : @rename($srcPath, $destination);

		if ($moved) {
			$return  = $onlyFilename ? $fileName : $path . DS . $fileName;
			$message = __('File uploaded successfully!');

			return ['success' => true, 'message' => $message, 'file' => $return, 'size' => $size];
		}

		$destination = $this->dirMedia . $path . DS;

		if (!is_writable((string)$destination)) {
			$message = sprintf(__('%s not writable!'), $destination);
		} else {
			$message = __('Error moving uploaded file!');
		}

		return ['success' => false, 'message' => $message, 'file' => $return, 'size' => $size];
	}

	/**
	 * Create a folder inside the media directory.
	 *
	 * @param string $path   Parent media sub-path, may be empty.
	 * @param string $folder New folder name.
	 *
	 * @return array{success: bool, message: string} Result.
	 */
	protected function createMediaFolder(string $path, string $folder): array {
		$folder  = sanitizeFileName($folder);
		$path    = sanitizeFileName($path);
		$success = false;

		$dirMedia = $this->dirMedia;

		if (is_dir($dirMedia . $path)) {
			if (is_dir($dirMedia . $path . DS . $folder)) {
				$message = __('Folder already exists!');
			} else {
				if (@mkdir($dirMedia . $path . DS . $folder)) {
					$message = __('Folder created!');
					$success = true;
				} else {
					$message = __('Error creating folder!');
				}
			}
		} else {
			$message = __('Path does not exist!');
		}

		return ['success' => $success, 'message' => $message];
	}

	/**
	 * Rename (or copy) a media file.
	 *
	 * @param string $file      Current media-relative file path.
	 * @param string $newfile   New media-relative path, alternative to $newname.
	 * @param string $newname   New file name inside the current directory.
	 * @param bool   $duplicate Copy instead of renaming.
	 *
	 * @return array{success: bool, message: string} Result.
	 */
	protected function renameMediaFile(string $file, string $newfile = '', string $newname = '', bool $duplicate = false): array {
		$file    = sanitizeFileName($file);
		$newfile = sanitizeFileName($newfile);
		$newname = sanitizeFileName($newname);

		$dirMedia = $this->dirMedia;

		$currentFile = $dirMedia . DS . $file;
		/** @var string $targetFile */
		$targetFile  = $currentFile;

		if ($newfile) {
			$targetFile = $dirMedia . DS . $newfile;
		}

		if ($newname) {
			$targetFile = dirname($currentFile) . DS . $newname;
		}

		//both ends must resolve inside the media dir (target parent is
		//checked since renames may point at not-yet-existing names)
		$mediaDir = rtrim($dirMedia, DS) . DS;

		foreach ([dirname($currentFile), dirname($targetFile)] as $parent) {
			$real = realpath($parent);

			if ($real === false || !str_starts_with(rtrim($real, DS) . DS, $mediaDir)) {
				return ['success' => false, 'message' => __('Invalid file!')];
			}
		}

		$extension = strtolower(substr($targetFile, strrpos($targetFile, '.') + 1));

		if (in_array($extension, $this->uploadDenyExtensions)) {
			return ['success' => false, 'message' => __('File type not allowed!')];
		}

		if ($duplicate) {
			if (@copy($currentFile, $targetFile)) {
				return ['success' => true, 'message' => __('File copied!')];
			}

			return ['success' => false, 'message' => __('Error copying file!')];
		}

		if (@rename($currentFile, $targetFile)) {
			return ['success' => true, 'message' => __('File renamed!')];
		}

		return ['success' => false, 'message' => __('Error renaming file!')];
	}

	/**
	 * Handle file upload(s) from the media manager.
	 *
	 * @return void
	 */
	public function upload(): void {
		/** @var array<int, array{name: list<string>, tmp_name: list<string>, error: list<int>, size: list<int>}> $files */
		$files      = $this->request->files['files'] ?? [];
		/** @var list<array{success: bool, message: string, file: string, size?: int}> $response */
		$response   = [];

		/** @var list<array{name: list<string>, tmp_name: list<string>, error: list<int>, size: list<int>}> $files */
		[$files] = Event::trigger(__CLASS__, __FUNCTION__, $files, $this);

		if ($files) {
			//normalize single-file uploads to the multi-file shape
			foreach (['name', 'tmp_name', 'error', 'size'] as $key) {
				if (isset($files[$key]) && !is_array($files[$key])) {
					$files[$key] = [$files[$key]];
				}
			}

			$length = count($files['name'] ?? []);

			for ($count = 0; $count < $length; $count++) {
				$fileName = sanitizeFileName($files['name'][$count]);

				if (!$fileName) {
					continue;
				}

				$stored = $this->storeMediaFile(
					$files['tmp_name'][$count],
					$files['name'][$count],
					(string)($this->request->post['mediaPath'] ?? ''),
					[
						'overwrite'    => $this->request->post['overwrite'] ?? false,
						'onlyFilename' => isset($this->request->post['onlyFilename']),
						'size'         => $files['size'][$count] ?? 0,
						'error'        => $files['error'][$count] ?? UPLOAD_ERR_OK,
						'httpUpload'   => true,
					]
				);

				if (($stored['success'] ?? false)) {
					$this->createMediaRecord($stored['file']);
				}

				$response[] = $stored;
			}
		} else {
			$response[] = ['success' => false, 'message' => __('Invalid upload!'), 'file' => ''];
		}

		$this->response->setType('json');
		$this->response->output($response);
	}

	/**
	 * Delete a file or directory from the media folder.
	 *
	 * @return void
	 */
	public function delete(): void {
		/** @var string|array<int, string> $file */
		$file        = $this->request->post['file'] ?? false;
		/** @var array{success: bool, message: string} $message */
		$message     = ['success' => false, 'message' => __('Error deleting file!')];
		$themeFolder = $this->dirMedia;

		if ($file) {
			if (is_array($file)) {
				foreach ($file as $f) {
					$f    = sanitizeFileName($f);
					$path = $themeFolder . DS . $f;

					if (@unlink($path)) {
						$message = ['success' => true, 'message' => __('File deleted!')];
					} else {
						$message = ['success' => false, 'message' => sprintf(__('Error deleting %s!'), $f)];
						break;
					}
				}
			} else {
				$file        = sanitizeFileName($this->request->post['file']);
				$path        = $themeFolder . DS . $file;

				if (is_dir($path)) {
					if (@rrmdir($path)) {
						$message = ['success' => true, 'message' => __('File deleted!')];
					}
				} else {
					if (@unlink($path)) {
						$message = ['success' => true, 'message' => __('File deleted!')];
					}
				}
			}
		}

		$this->response->setType('json');
		$this->response->output($message);
	}

	/**
	 * Rename or copy a file in the media folder.
	 *
	 * @return void
	 */
	public function rename(): void {
		$message = $this->renameMediaFile(
			(string)($this->request->post['file'] ?? ''),
			(string)($this->request->post['newfile'] ?? ''),
			(string)($this->request->post['newname'] ?? ''),
			(bool)($this->request->post['duplicate'] ?? false)
		);

		$this->response->setType('json');
		$this->response->output($message);
	}

	/**
	 * Create a new folder inside the media directory.
	 *
	 * @return void
	 */
	public function newFolder(): void {
		$message = $this->createMediaFolder(
			(string)($this->request->post['path'] ?? ''),
			(string)($this->request->post['folder'] ?? '')
		);

		$this->response->setType('json');
		$this->response->output($message);
	}

	public function _scan(string $path = '', $type = 'public', ?int $levels = null, int $start = 0, ?int $limit = null, ?int &$total = null): array {
		$path    = sanitizeFileName($path);
		$scandir = $this->dirMedia;

		if ($type) {
			/** @var string $type */
			$scandir = $this->dirForType($type);
		}

		if (!$scandir) {
			return [];
		}

		// node paths stay relative to the media root (not the requested
		// sub-path) so scoped listings splice straight into the client tree
		$mediaRoot = rtrim($scandir, DS);
		$scandir .= ($path ? DS . ltrim($path, DS) : '');

		// Scan one directory level; subfolders recurse only while the depth
		// budget lasts. $levels = 1 lists just the requested folder, deeper
		// folders come back with items=null as an "unloaded" marker for lazy
		// fetch. A null $levels keeps the legacy full-tree behavior.
		/** @var \Closure(string, int): list<array{name: string, type: string, path: string, items?: mixed[], size?: int}> $scan */
		$scan = function(string $dir, int $level = 1) use ($mediaRoot, &$scan, $levels): array {
			$files = [];

			// Is there actually such a folder/file?

			if (file_exists($dir)) {
				/** @var list<string>|false $listdir */
				$listdir = @\scandir($dir);

				if ($listdir) {
					foreach ($listdir as $f) {
						if (!$f || $f[0] === '.' || $f === 'node_modules' || $f === 'vendor') {
							continue; // Ignore hidden files
						}

						$path = '/' . trim(str_replace([$mediaRoot, '\\'], ['', '/'], $dir) . '/' . trim($f, '/'), '/');
						if (is_dir($dir . DS . $f)) {
							// The path is a folder
							if ($levels === null || $level < $levels) {
								$items = $scan($dir . DS . $f, $level + 1);
							} else {
								$items = null;
							}

							$files[] = [
								'name'  => $f,
								'type'  => 'folder',
								'path'  => $path,
								'items' => $items,
							];
						} else {
							// It is a file

							$files[] = [
								'name' => $f,
								'type' => 'file',
								'path' => $path,
								'size' => filesize("$dir/$f"),
							];
						}
					}
				}
			}

			return $files;
		};

		$response = $scan($scandir, 1);

		$total = count($response);

		if ($limit !== null) {
			$dirs  = [];
			$files = [];

			foreach ($response as $node) {
				if (($node['type'] ?? '') === 'folder') {
					$dirs[] = $node;
				} else {
					$files[] = $node;
				}
			}

			$response = array_slice(array_merge($dirs, $files), max(0, $start), max(0, $limit));
		}

		return $response;
	}

	/**
	 * List one media folder level, a scoped subtree, or filename matches.
	 *
	 * @param string   $path   Folder to list, default root.
	 * @param int|null $levels Depth budget, default 1 (null = full tree).
	 * @param string   $search Case-insensitive basename match, flat results.
	 * @param string   $type   Media type root (dirForType key or empty).
	 * @param int      $limit  Cap for search matches.
	 *
	 * @return mixed[] Root/folder envelope, flat {folders, files} for search.
	 */
	protected function listMedia(string $path = '', ?int $levels = null, string $search = '', $type = '', int $limit = 200, int $start = 0): array {
		if ($search !== '') {
			return $this->searchMedia($search, $type, $limit);
		}

		// default entry point used by the browser: root, one level only
		if ($path === '' && $levels === null) {
			$levels = 1;
		}

		$total    = 0;
		$response = $this->_scan($path, $type, $levels, $start, $limit, $total);

		if ($path === '') {
			if (!$response && !$total) {
				return [];
			}

			return [
				'name'  => '',
				'type'  => 'folder',
				'path'  => '',
				'items' => $response,
				'total' => $total,
			];
		}

		$nodePath = '/' . trim(str_replace('\\', '/', $path), '/');

		return [
			'name'  => basename($nodePath) ?: $nodePath,
			'type'  => 'folder',
			'path'  => $nodePath,
			'items' => $response,
			'total' => $total,
		];
	}

	/**
	 * List one media folder level, a scoped subtree, or filename matches.
	 *
	 * @param ?string $path
	 * @param ?int    $levels
	 *
	 * @return void
	 */
	public function scan(?string $path = null, ?int $levels = null): void {
		$get    = $this->request->get ?? [];
		$path   = isset($get['path']) ? (string)$get['path'] : (string)($path ?? '');
		$levels = isset($get['levels']) ? max(1, (int)$get['levels']) : $levels;
		$search = isset($get['search']) ? trim((string)$get['search']) : '';
		$start  = isset($get['start']) ? max(0, (int)$get['start']) : 0;
		$limit  = isset($get['limit']) ? max(1, min(500, (int)$get['limit'])) : 100;

		$type = '';
		if (isset($this->dirMediaType) && $this->dirMediaType) {
			/** @var string $type */
			$type    = $this->request->get['type'] ?? 'public';
		}

		$this->response->setType('json');
		$this->response->output($this->listMedia($path, $levels, $search, $type, $limit, $start));
	}

	/**
	 * Bounded recursive basename search for the media browser.
	 *
	 * @param string $query Search term, matched case-insensitively.
	 * @param string $type  Media type root (dirForType key or empty).
	 * @param int    $limit Max matches returned.
	 *
	 * @return array{folders: list<array<array-key, mixed>>, files: list<array<array-key, mixed>>} Flat matches.
	 */
	public function searchMedia(string $query, $type = 'public', int $limit = 200): array {
		$query = mb_strtolower(trim($query));

		if ($query === '') {
			return ['folders' => [], 'files' => []];
		}

		$scandir = $this->dirMedia;

		if ($type) {
			$scandir = $this->dirForType($type);
		}

		if (!$scandir || !file_exists($scandir)) {
			return ['folders' => [], 'files' => []];
		}

		$mediaRoot = rtrim($scandir, DS);
		$folders   = [];
		$files     = [];
		$found     = 0;
		$visited   = 0;
		$stack     = [$scandir];

		while ($stack && $found < $limit && $visited < 5000) {
			$dir = array_pop($stack);
			$visited++;

			$listdir = @\scandir($dir);

			if (!$listdir) {
				continue;
			}

			foreach ($listdir as $f) {
				if (!$f || $f[0] === '.' || $f === 'node_modules' || $f === 'vendor') {
					continue;
				}

				$full = $dir . DS . $f;
				$rel  = '/' . trim(str_replace([$mediaRoot, '\\'], ['', '/'], $dir) . '/' . trim($f, '/'), '/');

				if (is_dir($full)) {
					$stack[] = $full;

					if (mb_stripos($f, $query) !== false && $found < $limit) {
						$folders[] = ['name' => $f, 'type' => 'folder', 'path' => $rel];
						$found++;
					}
				} elseif (mb_stripos($f, $query) !== false && $found < $limit) {
					$files[] = ['name' => $f, 'type' => 'file', 'path' => $rel, 'size' => @filesize($full) ?: 0];
					$found++;
				}
			}
		}

		return ['folders' => $folders, 'files' => $files];
	}
}
