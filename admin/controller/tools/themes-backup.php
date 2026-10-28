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
use Vvveb\System\Extensions\Themes;
use function Vvveb\url;

class ThemesBackup extends Base {
	/**
	 * Base directory for themes backups.
	 *
	 * @var string
	 */
	private string $backupDir;

	/**
	 * Initialize backup directory.
	 */
	public function __construct() {
		$this->backupDir = DIR_BACKUP . 'themes' . DS;

		if (!is_dir($this->backupDir)) {
			@mkdir($this->backupDir, 0755, true);
		}
	}

	/**
	 * Get list of available themes (slug => info).
	 *
	 * @return array<string, array<string, mixed>>
	 */
	private function getThemesList(): array {
		return Themes::getList(SITE_ID, false);
	}

	/**
	 * Sanitize theme name (slug) - allow only alphanum, dash, underscore.
	 *
	 * @param string $name Theme slug
	 *
	 * @return string Sanitized slug
	 */
	private function sanitizeThemeName(string $name): string {
		$name = basename(trim($name));

		return preg_replace('/[^a-zA-Z0-9_\-]/', '', $name);
	}

	/**
	 * Create zip archive for a single theme.
	 *
	 * @param string $theme       Theme slug
	 * @param string $destination Full path to zip file
	 *
	 * @return bool True on success
	 */
	private function zipTheme(string $theme, string $destination): bool {
		$theme    = $this->sanitizeThemeName($theme);
		$source   = DIR_THEMES . $theme;

		if (!$theme || !is_dir($source)) {
			return false;
		}

		if (!class_exists('\ZipArchive')) {
			return false;
		}

		$zip = new \ZipArchive();

		if ($zip->open($destination, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
			return false;
		}

		$sourceReal = realpath($source);

		if ($sourceReal === false) {
			$zip->close();

			return false;
		}

		$iterator = new \RecursiveIteratorIterator(
			new \RecursiveDirectoryIterator($sourceReal, \RecursiveDirectoryIterator::SKIP_DOTS),
			\RecursiveIteratorIterator::LEAVES_ONLY
		);

		foreach ($iterator as $file) {
			/** @var \SplFileInfo $file */
			if (!$file->isDir()) {
				$filePath     = $file->getRealPath();
				$relativePath = $theme . '/' . substr($filePath, strlen($sourceReal) + 1);
				$zip->addFile($filePath, $relativePath);
			}
		}

		$zip->close();

		return file_exists($destination) && filesize($destination) > 0;
	}

	/**
	 * Extract a theme zip archive.
	 *
	 * @param string $zipFile Full path to zip file
	 *
	 * @return bool True on success
	 */
	private function unzipTheme(string $zipFile): bool {
		if (!file_exists($zipFile) || !class_exists('\ZipArchive')) {
			return false;
		}

		$zip = new \ZipArchive();

		if ($zip->open($zipFile) !== true) {
			return false;
		}

		$result = $zip->extractTo(DIR_THEMES);
		$zip->close();

		return $result;
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

		//if folder already exists within same minute, add seconds or increment
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
	 * Process next theme(s) for backup with time-limited execution.
	 *
	 * @return void
	 */
	public function nextBackup(): void {
		$folder      = sanitizeFileName($this->request->get['folder'] ?? '');
		$position    = (int)($this->request->get['position'] ?? 0);
		$themesParam = $this->request->get['themes'] ?? '';

		//themes can be passed as comma separated string or array
		if (is_array($themesParam)) {
			$themes = $themesParam;
		} else {
			$themes = $themesParam !== '' ? explode(',', (string)$themesParam) : [];
		}

		//also check POST for themes on first step fallback
		if (empty($themes) && isset($this->request->post['themes'])) {
			$themes = (array)$this->request->post['themes'];
		}

		// sanitize themes
		$themes = array_values(array_filter(array_map(fn ($p) => $this->sanitizeThemeName((string)$p), $themes)));

		//if no themes passed, try to read from folder metadata (themes.json) if exists
		if (empty($themes) && $folder && file_exists($this->backupDir . $folder . DS . 'themes.json')) {
			$json    = file_get_contents($this->backupDir . $folder . DS . 'themes.json');
			$themes  = json_decode($json, true) ?? [];
			$themes  = array_values(array_filter(array_map(fn ($p) => $this->sanitizeThemeName((string)$p), (array)$themes)));
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

		if (empty($themes)) {
			$error = __('No themes selected!');
			if ($this->request->isAjax()) {
				exit(json_encode(['error' => $error]));
			} else {
				$this->view->errors[] = $error;
				$this->index();

				return;
			}
		}

		// validate themes exist
		$available      = $this->getThemesList();
		$availableSlugs = array_keys($available);
		//also allow themes that may not be in getList but folder exists (maybe broken)
		foreach ($themes as $slug) {
			if (!in_array($slug, $availableSlugs, true) && !is_dir(DIR_THEMES . $slug)) {
				$error = sprintf(__('Theme "%s" not found!'), $slug);
				if ($this->request->isAjax()) {
					exit(json_encode(['error' => $error]));
				} else {
					$this->view->errors[] = $error;
					$this->index();

					return;
				}
			}
		}

		$count     = count($themes);
		$start     = microtime(true);
		$elapsed   = 0;
		$current   = '';

		//if takes longer than 8 seconds start a new process to avoid php timeout
		while ($elapsed < 8 && ($position < $count)) {
			$theme       = $themes[$position];
			$current     = $theme;
			$destination = $folderPath . DS . $theme . '.zip';

			$success = $this->zipTheme($theme, $destination);

			if (!$success) {
				$error = sprintf(__('Error backing up theme "%s"!'), $theme);
				if ($this->request->isAjax()) {
					exit(json_encode(['error' => $error, 'file' => $folder, 'position' => $position, 'count' => $count]));
				} else {
					$this->view->errors[] = $error;
					$this->index();

					return;
				}
			}

			$position++;

			$end     = microtime(true);
			$elapsed = $end - $start;
		}

		//if all done, remove metadata file if exists
		if ($position >= $count) {
			$metaFile = $folderPath . DS . 'themes.json';
			if (file_exists($metaFile)) {
				@unlink($metaFile);
			}

			$message = __('Backup finished!');
			$url     = ['module' => 'tools/themes-backup', 'success' => $message];

			if ($this->request->isAjax()) {
				exit(json_encode($url + ['url' => url($url), 'file' => $folder, 'theme' => $current, 'position' => $position, 'count' => $count, 'success' => $message]));
			} else {
				$this->redirect($url);
				$this->index();
			}
		} else {
			$themeName     = $themes[$position] ?? $current;
			$themesEncoded = implode(',', $themes);
			$url           = ['module' => 'tools/themes-backup', 'action' => 'nextBackup', 'folder' => $folder, 'themes' => $themesEncoded, 'theme' => $themeName, 'position' => $position, 'count' => $count];

			if ($this->request->isAjax()) {
				exit(json_encode($url + ['url' => url($url), 'file' => $folder, 'theme' => $themeName, 'position' => $position, 'count' => $count]));
			} else {
				$this->redirect($url);
				$this->index();
			}
		}
	}

	/**
	 * Process next theme(s) for restore with time-limited execution.
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

		//if file contains path, split
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

		if ($fileParam) {
			$singlePath = $folderPath . DS . $fileParam;
			if (!file_exists($singlePath) || !is_file($singlePath)) {
				$error = __('Backup does not exist!');
				if ($this->request->isAjax()) {
					exit(json_encode(['error' => $error, 'file' => $folder, 'position' => $position, 'count' => 1]));
				} else {
					$this->view->errors[] = $error;
					$this->index();

					return;
				}
			}
			$files = [$singlePath];
		} else {
			$files = glob($folderPath . DS . '*.zip') ?: [];
		}
		$count = count($files);
		$start = microtime(true);

		if ($count === 0) {
			$error = __('No theme backups found in folder!');
			if ($this->request->isAjax()) {
				exit(json_encode(['error' => $error]));
			} else {
				$this->view->errors[] = $error;
				$this->index();

				return;
			}
		}

		sort($files);
		$elapsed = 0;
		$current = '';

		while ($elapsed < 8 && ($position < $count)) {
			$file    = $files[$position];
			$current = basename($file);

			$success = $this->unzipTheme($file);

			if (!$success) {
				$error = sprintf(__('Error restoring theme "%s"!'), $current);
				if ($this->request->isAjax()) {
					exit(json_encode(['error' => $error, 'file' => $folder, 'position' => $position, 'count' => $count]));
				} else {
					$this->view->errors[] = $error;
					$this->index();

					return;
				}
			}

			$position++;
			$elapsed = microtime(true) - $start;
		}

		if ($position >= $count) {
			$message = __('Restore finished!');
			$url     = ['module' => 'tools/themes-backup', 'success' => $message];

			if ($this->request->isAjax()) {
				exit(json_encode($url + ['url' => url($url), 'file' => $folder, 'theme' => $current, 'position' => $position, 'count' => $count, 'success' => $message]));
			} else {
				$this->redirect($url);
				$this->view->success[] = $message;
				$this->index();
			}
		} else {
			$nextTheme = basename($files[$position] ?? $current);
			$url       = ['module' => 'tools/themes-backup', 'action' => 'nextRestore', 'folder' => $folder, 'theme' => $nextTheme, 'position' => $position, 'count' => $count];
			if ($fileParam) {
				$url['file'] = $fileParam;
			}

			if ($this->request->isAjax()) {
				$jsonExtra = ['url' => url($url), 'file' => $folder, 'theme' => $nextTheme, 'position' => $position, 'count' => $count];
				if ($fileParam) {
					$jsonExtra['file'] = $folder . '/' . $fileParam;
				}
				exit(json_encode($url + $jsonExtra));
			} else {
				$this->redirect($url);
				exit('Processing');
			}
		}
	}

	/**
	 * Delete a themes backup folder or single theme zip.
	 *
	 * @return void
	 */
	public function delete(): void {
		if ($this->request->getMethod() !== 'post') {
			$this->notFound(__('Invalid request method!'), 405);
		}

		$folder = sanitizeFileName($this->request->post['folder'] ?? $this->request->post['file'] ?? '');
		$file   = sanitizeFileName($this->request->post['file'] ?? '');

		//if file contains path separator, split folder and file
		if ($file && str_contains($file, DS)) {
			$parts  = explode(DS, $file);
			$folder = $parts[0];
			$file   = $parts[1] ?? '';
			//if more parts (should not), use last as file and first as folder
			if (count($parts) > 2) {
				$folder = $parts[0];
				$file   = end($parts);
			}
		} elseif ($file && !$folder) {
			//if file is actually folder name (legacy), treat as folder
			if (is_dir($this->backupDir . $file) && !str_contains($file, '.')) {
				$folder = $file;
				$file   = '';
			}
		}

		//also check for folder param with slash
		if ($folder && str_contains($folder, DS)) {
			//folder should not contain slash beyond base, sanitize to basename
			$folder = basename($folder);
		}

		if ($folder && $file) {
			//delete single theme zip
			$path = $this->backupDir . $folder . DS . $file;

			if (file_exists($path) && is_file($path)) {
				if (unlink($path)) {
					//if folder empty after delete, remove folder
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
	 * Initiate a themes restore from a backup folder or single theme file.
	 *
	 * @return void
	 */
	public function restore(): void {
		if ($this->request->getMethod() !== 'post') {
			$this->notFound(__('Invalid request method!'), 405);
		}

		$folder = sanitizeFileName($this->request->post['folder'] ?? $this->request->get['folder'] ?? '');
		$file   = sanitizeFileName($this->request->post['file'] ?? $this->request->get['file'] ?? '');

		//if file contains path, split
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
			//file param may be folder/file.zip without separate folder
			$folder = '';
		}

		if ($folder && str_contains($folder, DS)) {
			$folder = basename($folder);
		}
		if ($file) {
			$file = basename($file);
		}

		//fallback: if folder empty but file contains folder path via post file
		if (!$folder && $file && str_contains($this->request->post['file'] ?? '', '/')) {
			$raw = sanitizeFileName($this->request->post['file'] ?? '', false);
			//sanitize with normalized false to keep slash?
			$parts = explode('/', $raw);
			if (count($parts) === 2) {
				$folder = sanitizeFileName($parts[0]);
				$file   = sanitizeFileName($parts[1]);
			}
		}

		//legacy fallback: if folder contains .zip and no separate file
		if ($folder && str_contains($folder, '.zip') && !$file) {
			$file   = basename($folder);
			$folder = basename(dirname($this->request->post['file'] ?? $this->request->post['folder'] ?? ''));
			$folder = sanitizeFileName($folder);
		}

		//also handle case where folder param actually contains file path (old backup.js pattern)
		if (!$file && $folder && str_contains($this->request->post['file'] ?? '', '.zip')) {
			$fileCandidate = sanitizeFileName($this->request->post['file'] ?? '');
			if (str_contains($fileCandidate, '.zip')) {
				$file = basename($fileCandidate);
			}
		}

		if ($file) {
			$url  = ['module' => 'tools/themes-backup', 'action' => 'nextRestore', 'folder' => $folder, 'file' => $file];
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

		$url = ['module' => 'tools/themes-backup', 'action' => 'nextRestore', 'folder' => $folder];

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
	 * Download a theme backup file or entire backup folder.
	 *
	 * @return void
	 */
	public function download(): void {
		$folder = sanitizeFileName($this->request->post['folder'] ?? $this->request->get['folder'] ?? '');
		$file   = sanitizeFileName($this->request->post['file'] ?? $this->request->get['file'] ?? '');

		//if file contains folder separator, split
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
				//create temporary zip of folder for download
				$tmpFile = tempnam(sys_get_temp_dir(), 'themes_backup_') . '.zip';
				$zip     = new \ZipArchive();

				if ($zip->open($tmpFile, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) === true) {
					$files = new \RecursiveIteratorIterator(
						new \RecursiveDirectoryIterator($folderPath, \RecursiveDirectoryIterator::SKIP_DOTS),
						\RecursiveIteratorIterator::LEAVES_ONLY
					);
					foreach ($files as $f) {
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
	 * Initiate the themes backup process.
	 *
	 * @return void
	 */
	public function save(): void {
		$themes = $this->request->post['themes'] ?? [];

		if (is_string($themes)) {
			$themes = $themes !== '' ? explode(',', $themes) : [];
		}

		if (!is_array($themes)) {
			$themes = (array)$themes;
		}

		$themes = array_values(array_filter(array_map(fn ($p) => $this->sanitizeThemeName((string)$p), $themes)));

		if (empty($themes)) {
			$error = __('No themes selected!');
			if ($this->request->isAjax()) {
				exit(json_encode(['error' => $error]));
			} else {
				$this->view->errors[] = $error;
				$this->index();

				return;
			}
		}

		//validate themes exist
		$available      = $this->getThemesList();
		$availableSlugs = array_keys($available);

		foreach ($themes as $slug) {
			if (!in_array($slug, $availableSlugs, true) && !is_dir(DIR_THEMES . $slug)) {
				$error = sprintf(__('Theme "%s" not found!'), $slug);
				if ($this->request->isAjax()) {
					exit(json_encode(['error' => $error]));
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

		//store themes list for next steps (in case url truncated, allow resume)
		@file_put_contents($folderPath . DS . 'themes.json', json_encode($themes));

		$themesEncoded = implode(',', $themes);
		$url           = ['module' => 'tools/themes-backup', 'action' => 'nextBackup', 'folder' => $folder, 'themes' => $themesEncoded, 'position' => 0, 'count' => count($themes)];

		if ($this->request->isAjax()) {
			exit(json_encode($url + ['url' => url($url), 'position' => 0, 'count' => count($themes), 'theme' => $themes[0], 'file' => $folder]));
		} else {
			$this->redirect($url);
			$this->index();
		}
	}

	/**
	 * Display the themes backup management page.
	 *
	 * @return void
	 */
	public function index(): void {
		$view = $this->view;

		//available themes for selection
		$themesList = $this->getThemesList();
		$themes     = [];

		foreach ($themesList as $slug => $info) {
			$themes[] = [
				'slug'        => $slug,
				'name'        => $info['name'] ?? $slug,
				'description' => $info['description'] ?? '',
				'version'     => $info['version'] ?? '',
				'thumb_url'   => $info['thumb_url'] ?? '',
				'status'      => $info['status'] ?? 'inactive',
			];
		}

		//sort themes by name
		usort($themes, fn ($a, $b) => strcmp($a['name'], $b['name']));

		$view->themes     = $themes;
		$view->themeCount = count($themes);

		//existing backups
		$backupFolders = glob($this->backupDir . '*', GLOB_ONLYDIR) ?: [];
		$backups       = [];

		foreach ($backupFolders as $index => $folderPath) {
			$folder = basename($folderPath);
			$files  = glob($folderPath . DS . '*.zip') ?: [];
			$size   = 0;

			foreach ($files as $f) {
				$size += filesize($f);
			}

			//collect theme files info
			$themeFiles = [];

			foreach ($files as $f) {
				$name         = basename($f);
				$slug         = pathinfo($name, PATHINFO_FILENAME);
				$themeFiles[] = [
					'name'         => $name,
					'file'         => $name,
					'slug'         => $slug,
					'folder'       => $folder,
					'file_path'    => $folder . '/' . $name,
					'size'         => formatBytes(filesize($f)),
					'size_bytes'   => filesize($f),
					'download-url' => url(['module' => 'tools/themes-backup', 'action' => 'download']),
					'delete-url'   => url(['module' => 'tools/themes-backup', 'action' => 'delete']),
					'restore-url'  => url(['module' => 'tools/themes-backup', 'action' => 'restore']),
				];
			}

			$backups[] = [
				'name'         => $folder,
				'folder'       => $folder,
				'file'         => $folder, //for compatibility with backup.js expecting file
				'key'          => $index,
				'size_bytes'   => $size,
				'size'         => formatBytes($size),
				'created_at'   => date('Y/m/d H:i', filemtime($folderPath)),
				'count'        => count($files),
				'files'        => $themeFiles,
				'restore-url'  => url(['module' => 'tools/themes-backup', 'action' => 'restore']),
				'download-url' => url(['module' => 'tools/themes-backup', 'action' => 'download']),
				'delete-url'   => url(['module' => 'tools/themes-backup', 'action' => 'delete']),
			];
		}

		//sort backups by date desc (newest first)
		usort($backups, fn ($a, $b) => strcmp($b['name'], $a['name']));

		$view->backups   = $backups;
		$view->backupUrl = url(['module' => 'tools/themes-backup', 'action' => 'save']);
		$view->hasZip    = class_exists('\ZipArchive');
	}
}
