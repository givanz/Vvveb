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

namespace Vvveb\Controller\Tools;

use function Vvveb\__;
use Vvveb\Controller\Base;
use function Vvveb\formatBytes;
use function Vvveb\sanitizeFileName;
use function Vvveb\url;

class MediaBackup extends Base {
	/**
	 * Base directory for media backups.
	 *
	 * @var string
	 */
	private string $backupDir;

	/**
	 * Initialize backup directory.
	 */
	public function __construct() {
		$this->backupDir = DIR_BACKUP . 'media' . DS;

		if (!is_dir($this->backupDir)) {
			@mkdir($this->backupDir, 0755, true);
		}
	}

	/**
	 * Generate unique folder name for backup run (Y-m-d-H-i).
	 *
	 * @return string Folder name
	 */
	private function generateFolderName(): string {
		$folder = date('Y-m-d-H-i');
		$folder = sanitizeFileName($folder);
		$base   = $this->backupDir . $folder;

		if (!is_dir($base)) {
			return $folder;
		}

		$folderWithSeconds = date('Y-m-d-H-i-s');
		$folderWithSeconds = sanitizeFileName($folderWithSeconds);

		if (!is_dir($this->backupDir . $folderWithSeconds)) {
			return $folderWithSeconds;
		}

		$i = 1;
		while (is_dir($this->backupDir . $folder . '-' . $i)) {
			$i++;
		}

		return $folder . '-' . $i;
	}

	/**
	 * Get list of all media files relative to DIR_MEDIA.
	 *
	 * @return list<string>
	 */
	private function getMediaFiles(): array {
		$files    = [];
		$mediaDir = rtrim(DIR_MEDIA, DS);

		if (!is_dir($mediaDir)) {
			return $files;
		}

		$iterator = new \RecursiveIteratorIterator(
			new \RecursiveDirectoryIterator($mediaDir, \RecursiveDirectoryIterator::SKIP_DOTS),
			\RecursiveIteratorIterator::SELF_FIRST
		);

		foreach ($iterator as $file) {
			$fullPath = $file->getPathname();
			$relative = substr($fullPath, strlen($mediaDir) + 1);
			// store relative path, include directories as well to preserve empty dirs
			$files[] = $relative;
		}

		sort($files);

		return $files;
	}

	/**
	 * Process next chunk for media backup with time-limited execution.
	 *
	 * @return void
	 */
	public function nextBackup(): void {
		$folder   = sanitizeFileName($this->request->get['folder'] ?? '');
		$position = (int)($this->request->get['position'] ?? 0);

		if (!$folder) {
			$error = __('Invalid backup folder!');
			if ($this->request->isAjax()) {
				exit(json_encode(['error' => $error]));
			} else {
				$this->view->errors[] = $error;
				$this->index();

				return;
			}
		}

		$folderPath = $this->backupDir . $folder;

		if (!is_dir($folderPath)) {
			$error = __('Backup folder does not exist!');
			if ($this->request->isAjax()) {
				exit(json_encode(['error' => $error]));
			} else {
				$this->view->errors[] = $error;
				$this->index();

				return;
			}
		}

		if (!class_exists('\ZipArchive')) {
			$error = __('ZipArchive extension is not available!');
			if ($this->request->isAjax()) {
				exit(json_encode(['error' => $error]));
			} else {
				$this->view->errors[] = $error;
				$this->index();

				return;
			}
		}

		$mediaDir = rtrim(DIR_MEDIA, DS);

		if (!is_dir($mediaDir)) {
			$error = __('Media folder does not exist!');
			if ($this->request->isAjax()) {
				exit(json_encode(['error' => $error]));
			} else {
				$this->view->errors[] = $error;
				$this->index();

				return;
			}
		}

		$files   = $this->getMediaFiles();
		$count   = count($files);
		$zipPath = $folderPath . DS . 'media.zip';

		// handle empty media folder
		if ($count === 0) {
			// create empty zip with just folder
			$zip = new \ZipArchive();
			if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) === true) {
				$zip->addEmptyDir('media');
				$zip->close();
			}
			$message = __('Backup finished!');
			$url     = ['module' => 'tools/media-backup', 'success' => $message];
			if ($this->request->isAjax()) {
				exit(json_encode($url + ['url' => url($url), 'file' => $folder, 'position' => $count, 'count' => $count, 'success' => $message]));
			} else {
				$this->redirect($url);
				$this->index();
			}

			return;
		}

		$start   = microtime(true);
		$elapsed = 0;
		$current = '';

		$zip   = new \ZipArchive();
		$flags = \ZipArchive::CREATE;
		if ($position === 0) {
			$flags |= \ZipArchive::OVERWRITE;
		}
		if ($zip->open($zipPath, $flags) !== true) {
			$error = __('Error creating backup archive!');
			if ($this->request->isAjax()) {
				exit(json_encode(['error' => $error, 'file' => $folder, 'position' => $position, 'count' => $count]));
			} else {
				$this->view->errors[] = $error;
				$this->index();

				return;
			}
		}

		while ($elapsed < 8 && ($position < $count)) {
			$relative = $files[$position];
			$current  = $relative;
			$fullPath = $mediaDir . DS . $relative;

			if (is_dir($fullPath)) {
				$zip->addEmptyDir($relative);
			} elseif (is_file($fullPath)) {
				$zip->addFile($fullPath, $relative);
			}

			$position++;
			$elapsed = microtime(true) - $start;
		}

		$zip->close();

		if ($position >= $count) {
			$message = __('Backup finished!');
			$url     = ['module' => 'tools/media-backup', 'success' => $message];

			if ($this->request->isAjax()) {
				exit(json_encode($url + ['url' => url($url), 'file' => $folder, 'position' => $position, 'count' => $count, 'success' => $message]));
			} else {
				$this->redirect($url);
				$this->index();
			}
		} else {
			$url = ['module' => 'tools/media-backup', 'action' => 'nextBackup', 'folder' => $folder, 'position' => $position, 'count' => $count];

			if ($this->request->isAjax()) {
				exit(json_encode($url + ['url' => url($url), 'file' => $folder, 'position' => $position, 'count' => $count]));
			} else {
				$this->redirect($url);
				$this->index();
			}
		}
	}

	/**
	 * Process next chunk for media restore with time-limited execution.
	 *
	 * @return void
	 */
	public function nextRestore(): void {
		if ($this->request->getMethod() !== 'post') {
			$this->notFound(__('Invalid request method!'), 405);
		}

		$folder    = sanitizeFileName($this->request->get['folder'] ?? $this->request->post['folder'] ?? '');
		$fileParam = sanitizeFileName($this->request->get['file'] ?? $this->request->post['file'] ?? '');
		$position  = (int)($this->request->get['position'] ?? 0);

		if ($fileParam && str_contains($fileParam, DS)) {
			$parts = explode(DS, $fileParam);
			if (count($parts) === 2) {
				$folder    = $parts[0] ?: $folder;
				$fileParam = $parts[1];
			} else {
				$folder    = $parts[0] ?: $folder;
				$fileParam = end($parts);
			}
		}

		if ($fileParam) {
			$fileParam = basename($fileParam);
		}

		if (!$folder) {
			$error = __('Invalid backup folder!');
			if ($this->request->isAjax()) {
				exit(json_encode(['error' => $error]));
			} else {
				$this->view->errors[] = $error;
				$this->index();

				return;
			}
		}

		$folderPath = $this->backupDir . $folder;

		if (!is_dir($folderPath)) {
			$error = __('Backup folder does not exist!');
			if ($this->request->isAjax()) {
				exit(json_encode(['error' => $error]));
			} else {
				$this->view->errors[] = $error;
				$this->index();

				return;
			}
		}

		// determine zip to restore: if file param set use it, else media.zip
		$zipFile = $folderPath . DS . ($fileParam ?: 'media.zip');

		if (!file_exists($zipFile) || !is_file($zipFile)) {
			// fallback to any zip in folder
			$zips = glob($folderPath . DS . '*.zip') ?: [];
			if (!empty($zips)) {
				$zipFile   = $zips[0];
				$fileParam = basename($zipFile);
			} else {
				$error = __('Backup does not exist!');
				if ($this->request->isAjax()) {
					exit(json_encode(['error' => $error, 'file' => $folder, 'position' => $position, 'count' => 1]));
				} else {
					$this->view->errors[] = $error;
					$this->index();

					return;
				}
			}
		}

		if (!class_exists('\ZipArchive')) {
			$error = __('ZipArchive extension is not available!');
			if ($this->request->isAjax()) {
				exit(json_encode(['error' => $error]));
			} else {
				$this->view->errors[] = $error;
				$this->index();

				return;
			}
		}

		$zip = new \ZipArchive();
		if ($zip->open($zipFile) !== true) {
			$error = __('Error opening backup archive!');
			if ($this->request->isAjax()) {
				exit(json_encode(['error' => $error]));
			} else {
				$this->view->errors[] = $error;
				$this->index();

				return;
			}
		}

		$count   = $zip->numFiles;
		$start   = microtime(true);
		$elapsed = 0;
		$current = '';

		if ($count === 0) {
			$zip->close();
			$message = __('Restore finished!');
			$url     = ['module' => 'tools/media-backup', 'success' => $message];
			if ($this->request->isAjax()) {
				exit(json_encode($url + ['url' => url($url), 'file' => $folder, 'position' => $count, 'count' => $count, 'success' => $message]));
			} else {
				$this->redirect($url);
				$this->view->success[] = $message;
				$this->index();
			}

			return;
		}

		// Ensure media dir exists
		if (!is_dir(DIR_MEDIA)) {
			@mkdir(DIR_MEDIA, 0755, true);
		}

		while ($elapsed < 8 && ($position < $count)) {
			$stat    = $zip->statIndex($position);
			$current = $stat['name'] ?? (string)$position;
			// extract single file
			$zip->extractTo(DIR_MEDIA, [$stat['name']]);
			$position++;
			$elapsed = microtime(true) - $start;
		}

		$zip->close();

		if ($position >= $count) {
			$message = __('Restore finished!');
			$url     = ['module' => 'tools/media-backup', 'success' => $message];

			if ($this->request->isAjax()) {
				exit(json_encode($url + ['url' => url($url), 'file' => $folder, 'position' => $position, 'count' => $count, 'success' => $message]));
			} else {
				$this->redirect($url);
				$this->view->success[] = $message;
				$this->index();
			}
		} else {
			$url = ['module' => 'tools/media-backup', 'action' => 'nextRestore', 'folder' => $folder, 'position' => $position, 'count' => $count];
			if ($fileParam && $fileParam !== 'media.zip') {
				$url['file'] = $fileParam;
			}

			if ($this->request->isAjax()) {
				$jsonExtra = ['url' => url($url), 'file' => $folder, 'position' => $position, 'count' => $count];
				exit(json_encode($url + $jsonExtra));
			} else {
				$this->redirect($url);
				exit('Processing');
			}
		}
	}

	/**
	 * Delete a media backup folder or single file.
	 *
	 * @return void
	 */
	public function delete(): void {
		if ($this->request->getMethod() !== 'post') {
			$this->notFound(__('Invalid request method!'), 405);
		}

		$folder = sanitizeFileName($this->request->post['folder'] ?? $this->request->post['file'] ?? '');
		$file   = sanitizeFileName($this->request->post['file'] ?? '');

		if ($file && str_contains($file, DS)) {
			$parts  = explode(DS, $file);
			$folder = $parts[0];
			$file   = $parts[1] ?? '';
			if (count($parts) > 2) {
				$folder = $parts[0];
				$file   = end($parts);
			}
		} elseif ($file && !$folder) {
			if (is_dir($this->backupDir . $file) && !str_contains($file, '.')) {
				$folder = $file;
				$file   = '';
			}
		}

		if ($folder && str_contains($folder, DS)) {
			$folder = basename($folder);
		}

		if ($folder && $file) {
			$path = $this->backupDir . $folder . DS . $file;

			if (file_exists($path) && is_file($path)) {
				if (unlink($path)) {
					$remaining = glob($this->backupDir . $folder . DS . '*.zip');
					if (empty($remaining)) {
						@rmdir($this->backupDir . $folder);
					}
					$this->view->success[] = __('Backup deleted!');
				} else {
					$this->view->errors[] = __('Error deleting backup!');
				}
			} else {
				$this->view->errors[] = __('Backup does not exist!');
			}
		} elseif ($folder) {
			$path = $this->backupDir . $folder;

			if (is_dir($path)) {
				$result = \Vvveb\rrmdir($path);
				@rmdir($path);
				if ($result) {
					$this->view->success[] = __('Backup deleted!');
				} else {
					$this->view->errors[] = __('Error deleting backup!');
				}
			} elseif (file_exists($path) && is_file($path)) {
				if (unlink($path)) {
					$this->view->success[] = __('Backup deleted!');
				} else {
					$this->view->errors[] = __('Error deleting backup!');
				}
			} else {
				$this->view->errors[] = __('Backup does not exist!');
			}
		} else {
			$this->view->errors[] = __('Invalid backup!');
		}

		$this->index();
	}

	/**
	 * Initiate a media restore from a backup folder or single file.
	 *
	 * @return void
	 */
	public function restore(): void {
		if ($this->request->getMethod() !== 'post') {
			$this->notFound(__('Invalid request method!'), 405);
		}

		$folder = sanitizeFileName($this->request->post['folder'] ?? $this->request->get['folder'] ?? '');
		$file   = sanitizeFileName($this->request->post['file'] ?? $this->request->get['file'] ?? '');

		if ($file && str_contains($file, DS)) {
			$parts = explode(DS, $file);
			if (count($parts) === 2) {
				$folder = $parts[0] ?: $folder;
				$file   = $parts[1];
			} else {
				$folder = $parts[0] ?: $folder;
				$file   = end($parts);
			}
		} elseif ($file && !$folder && str_contains($file, '.zip')) {
			$folder = '';
		}

		if ($folder && str_contains($folder, DS)) {
			$folder = basename($folder);
		}
		if ($file) {
			$file = basename($file);
		}

		if (!$folder && $file && str_contains($this->request->post['file'] ?? '', '/')) {
			$raw   = sanitizeFileName($this->request->post['file'] ?? '', false);
			$parts = explode('/', $raw);
			if (count($parts) === 2) {
				$folder = sanitizeFileName($parts[0]);
				$file   = sanitizeFileName($parts[1]);
			}
		}

		if ($folder && str_contains($folder, '.zip') && !$file) {
			$file   = basename($folder);
			$folder = basename(dirname($this->request->post['file'] ?? $this->request->post['folder'] ?? ''));
			$folder = sanitizeFileName($folder);
		}

		if (!$file && $folder && str_contains($this->request->post['file'] ?? '', '.zip')) {
			$fileCandidate = sanitizeFileName($this->request->post['file'] ?? '');
			if (str_contains($fileCandidate, '.zip')) {
				$file = basename($fileCandidate);
			}
		}

		if ($file) {
			$url  = ['module' => 'tools/media-backup', 'action' => 'nextRestore', 'folder' => $folder, 'file' => $file];
			$path = $this->backupDir . $folder . DS . $file;
			if ($folder && is_dir($this->backupDir . $folder) && file_exists($path)) {
				if ($this->request->isAjax()) {
					exit(json_encode($url + ['url' => url($url)]));
				} else {
					$this->redirect($url);
					$this->index();
				}
			} else {
				$error = __('Backup does not exist!');
				if ($this->request->isAjax()) {
					exit(json_encode($url + ['error' => $error]));
				} else {
					$this->view->errors[] = $error;
					$this->index();
				}
			}

			return;
		}

		$url = ['module' => 'tools/media-backup', 'action' => 'nextRestore', 'folder' => $folder];

		if ($folder && is_dir($this->backupDir . $folder)) {
			if ($this->request->isAjax()) {
				exit(json_encode($url + ['url' => url($url)]));
			} else {
				$this->redirect($url);
				$this->index();
			}
		} else {
			$error = __('Backup does not exist!');

			if ($this->request->isAjax()) {
				exit(json_encode($url + ['error' => $error]));
			} else {
				$this->view->errors[] = $error;
				$this->index();
			}
		}
	}

	/**
	 * Download a media backup file or entire backup folder.
	 *
	 * @return void
	 */
	public function download(): void {
		$folder = sanitizeFileName($this->request->post['folder'] ?? $this->request->get['folder'] ?? '');
		$file   = sanitizeFileName($this->request->post['file'] ?? $this->request->get['file'] ?? '');

		if ($file && str_contains($file, DS)) {
			$parts  = explode(DS, $file);
			if (count($parts) === 2) {
				$folder = $parts[0];
				$file   = $parts[1];
			} else {
				$folder = $parts[0];
				$file   = end($parts);
			}
		}

		if ($folder && $file) {
			$path = $this->backupDir . $folder . DS . $file;

			if (file_exists($path) && is_file($path)) {
				$fp = fopen($path, 'r');
				header('Content-Type: application/zip');
				header('Content-Length: ' . filesize($path));
				header('Content-Disposition: attachment; filename="' . $file . '"');
				fpassthru($fp);
				exit();
			} else {
				$this->view->errors[] = __('Backup does not exist!');
			}
		} elseif ($folder) {
			$folderPath = $this->backupDir . $folder;

			if (is_dir($folderPath)) {
				$files = glob($folderPath . DS . '*.zip') ?: [];
				if (count($files) === 1) {
					// single media.zip, send directly
					$path = $files[0];
					$fp   = fopen($path, 'r');
					header('Content-Type: application/zip');
					header('Content-Length: ' . filesize($path));
					header('Content-Disposition: attachment; filename="' . basename($path) . '"');
					fpassthru($fp);
					exit();
				}
				// fallback: create temp zip of folder
				$tmpFile = tempnam(sys_get_temp_dir(), 'media_backup_') . '.zip';
				$zip     = new \ZipArchive();

				if ($zip->open($tmpFile, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) === true) {
					$iterator = new \RecursiveIteratorIterator(
						new \RecursiveDirectoryIterator($folderPath, \RecursiveDirectoryIterator::SKIP_DOTS),
						\RecursiveIteratorIterator::LEAVES_ONLY
					);
					foreach ($iterator as $f) {
						$real     = $f->getRealPath();
						$relative = substr($real, strlen($folderPath) + 1);
						$zip->addFile($real, $relative);
					}
					$zip->close();
					header('Content-Type: application/zip');
					header('Content-Length: ' . filesize($tmpFile));
					header('Content-Disposition: attachment; filename="' . $folder . '.zip"');
					readfile($tmpFile);
					@unlink($tmpFile);
					exit();
				} else {
					$this->view->errors[] = __('Error creating archive!');
				}
			} else {
				$this->view->errors[] = __('Backup does not exist!');
			}
		} else {
			$this->view->errors[] = __('Invalid backup!');
		}

		$this->index();
	}

	/**
	 * Initiate the media backup process.
	 *
	 * @return void
	 */
	public function save(): void {
		if (!class_exists('\ZipArchive')) {
			$error = __('ZipArchive extension is not available!');
			if ($this->request->isAjax()) {
				exit(json_encode(['error' => $error]));
			} else {
				$this->view->errors[] = $error;
				$this->index();

				return;
			}
		}

		if (!is_dir(DIR_MEDIA)) {
			$error = __('Media folder does not exist!');
			if ($this->request->isAjax()) {
				exit(json_encode(['error' => $error]));
			} else {
				$this->view->errors[] = $error;
				$this->index();

				return;
			}
		}

		$folder     = $this->generateFolderName();
		$folderPath = $this->backupDir . $folder;

		if (!@mkdir($folderPath, 0755, true) && !is_dir($folderPath)) {
			$error = __('Error creating backup folder!');
			if ($this->request->isAjax()) {
				exit(json_encode(['error' => $error]));
			} else {
				$this->view->errors[] = $error;
				$this->index();

				return;
			}
		}

		$url = ['module' => 'tools/media-backup', 'action' => 'nextBackup', 'folder' => $folder, 'position' => 0, 'count' => 0];

		if ($this->request->isAjax()) {
			exit(json_encode($url + ['url' => url($url), 'position' => 0, 'count' => 0, 'file' => $folder]));
		} else {
			$this->redirect($url);
			$this->index();
		}
	}

	/**
	 * Display the media backup management page.
	 *
	 * @return void
	 */
	public function index(): void {
		$view = $this->view;

		// media stats
		$mediaFiles       = $this->getMediaFiles();
		$view->mediaCount = count($mediaFiles);
		$mediaSize        = 0;
		foreach ($mediaFiles as $rel) {
			$full = DIR_MEDIA . $rel;
			if (is_file($full)) {
				$mediaSize += filesize($full);
			}
		}
		$view->mediaSize      = formatBytes($mediaSize);
		$view->mediaSizeBytes = $mediaSize;

		// existing backups
		$backupFolders = glob($this->backupDir . '*', GLOB_ONLYDIR) ?: [];
		$backups       = [];

		foreach ($backupFolders as $index => $folderPath) {
			$folder = basename($folderPath);
			$files  = glob($folderPath . DS . '*.zip') ?: [];
			$size   = 0;

			foreach ($files as $f) {
				$size += filesize($f);
			}

			$backupFiles = [];

			foreach ($files as $f) {
				$name          = basename($f);
				$backupFiles[] = [
					'name'         => $name,
					'file'         => $name,
					'slug'         => pathinfo($name, PATHINFO_FILENAME),
					'folder'       => $folder,
					'file_path'    => $folder . '/' . $name,
					'size'         => formatBytes(filesize($f)),
					'size_bytes'   => filesize($f),
					'download-url' => url(['module' => 'tools/media-backup', 'action' => 'download']),
					'delete-url'   => url(['module' => 'tools/media-backup', 'action' => 'delete']),
					'restore-url'  => url(['module' => 'tools/media-backup', 'action' => 'restore']),
				];
			}

			$backups[] = [
				'name'         => $folder,
				'folder'       => $folder,
				'file'         => $folder,
				'key'          => $index,
				'size_bytes'   => $size,
				'size'         => formatBytes($size),
				'created_at'   => date('Y/m/d H:i', filemtime($folderPath)),
				'count'        => count($files),
				'files'        => $backupFiles,
				'restore-url'  => url(['module' => 'tools/media-backup', 'action' => 'restore']),
				'download-url' => url(['module' => 'tools/media-backup', 'action' => 'download']),
				'delete-url'   => url(['module' => 'tools/media-backup', 'action' => 'delete']),
			];
		}

		usort($backups, fn ($a, $b) => strcmp($b['name'], $a['name']));

		$view->backups   = $backups;
		$view->backupUrl = url(['module' => 'tools/media-backup', 'action' => 'save']);
		$view->hasZip    = class_exists('\ZipArchive');
	}
}
