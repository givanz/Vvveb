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

namespace Vvveb\Controller\Media;

use function Vvveb\__;
use Vvveb\Controller\Base;
use function Vvveb\sanitizeFileName;
use Vvveb\System\Core\View;

class Media extends Base {
	protected $uploadDenyExtensions = ['php'];

	//protected $uploadAllowExtensions = ['ico','jpg','jpeg','png','gif','webp', 'mp4', 'mkv', 'mov'];

	function dirForType($type) {
		switch ($type) {
			case 'public':
				$scandir = DIR_MEDIA;

			break;

			case 'plugins':
				$scandir = DIR_PLUGINS;

			break;

			case 'themes':
				$scandir = DIR_THEMES;

			break;

			default:
				return false;
		}

		return $scandir;
	}

	function index() {
		$admin_path          = \Vvveb\adminPath();
		$controllerPath      = $admin_path . 'index.php?module=media/media';

		$this->view->scanUrl   = "$controllerPath&action=scan";
		$this->view->uploadUrl = "$controllerPath&action=upload";
		$this->view->deleteUrl = "$controllerPath&action=delete";
		$this->view->renameUrl = "$controllerPath&action=rename";
	}

	function upload() {
		$path      = sanitizeFileName($this->request->post['mediaPath']);
		$file      = $this->request->files['file'];
		$fileName  = sanitizeFileName($file['name']);
		$path      = str_replace(['/media', '/public/media'], '', $path);
		$extension = strtolower(substr($fileName, strrpos($fileName, '.') + 1));

		if (in_array($extension, $this->uploadDenyExtensions)) {
			die(__('File type not allowed!'));
		}

		switch ($file['error']) {
			case UPLOAD_ERR_OK:
				break;

			case UPLOAD_ERR_NO_FILE:
				die(__('No file sent.'));

			case UPLOAD_ERR_INI_SIZE:
			case UPLOAD_ERR_FORM_SIZE:
				die(__('Exceeded filesize limit.'));

			default:
				die(__('Unknown errors.'));
		}

		$destination = DIR_MEDIA . $path . '/' . $fileName;

		if (move_uploaded_file($file['tmp_name'], $destination)) {
			if (isset($this->request->post['onlyFilename'])) {
				echo $file['name'];
			} else {
				echo $destination;
			}
		} else {
			echo __('Error uploading file!');
		}

		die();
	}

	function delete() {
		$file        = sanitizeFileName($this->request->post['file']);
		$themeFolder = DIR_MEDIA;
		//echo $themeFolder . DS . $file;
		header('Content-type: application/json; charset=utf-8');

		if (unlink($themeFolder . DS . $file)) {
			$message = ['success' => true, 'message' => __('File deleted!')];
		} else {
			$message = ['success' => false, 'message' => __('Error deleting file!')];
		}

		echo json_encode($message);

		die();
	}

	function rename() {
		$file        = sanitizeFileName($this->request->post['file']);
		$newfile     = sanitizeFileName($this->request->post['newfile']);
		$duplicate   =  $this->request->post['duplicate'] ?? false;
		$themeFolder = DIR_MEDIA;

		header('Content-type: application/json; charset=utf-8');
		$currentFile = $themeFolder . DS . $file;
		$targetFile  =  $themeFolder . DS . $newfile;

		if ($duplicate) {
			if (copy($currentFile, $targetFile)) {
				$message = ['success' => true, 'message' => __('File copied!')];
			} else {
				$message = ['success' => false, 'message' => __('Error copying file!')];
			}
		} else {
			if (rename($currentFile, $targetFile)) {
				$message = ['success' => true, 'message' => __('File renamed!')];
			} else {
				$message = ['success' => false, 'message' => __('Error renaming file!')];
			}
		}

		echo json_encode($message);

		die();
	}

	function scan() {
		$type          = $this->request->get['type'] ?? 'public';
		$scandir       = $this->dirForType($type);

		if (! $scandir) {
			return [];
		}

		// This function scans the files folder recursively, and builds a large array
		$scan = function ($dir) use ($scandir, &$scan) {
			$files = [];

			// Is there actually such a folder/file?

			if (file_exists($dir)) {
				$files = @scandir($dir);

				if ($files) {
					foreach ($files as $f) {
						if (! $f || $f[0] == '.' || $f == 'node_modules' || $f == 'vendor') {
							continue; // Ignore hidden files
						}

						if (is_dir($dir . DS . $f)) {
							// The path is a folder

							$files[] = [
								'name'  => $f,
								'type'  => 'folder',
								'path'  => str_replace($scandir, '', $dir) . DS . $f,
								'items' => $scan($dir . DS . $f), // Recursively get the contents of the folder
							];
						} else {
							// It is a file

							$files[] = [
								'name' => $f,
								'type' => 'file',
								'path' => str_replace($scandir, '', $dir) . DS . $f,
								'size' => filesize($dir . DS . $f), // Gets the size of this file
							];
						}
					}
				}
			}

			return $files;
		};

		$response = $scan($scandir);

		// Output the directory listing as JSON
		$this->response->setType('json');
		$this->response->output([
			'name'  => '',
			'type'  => 'folder',
			'path'  => '',
			'items' => $response,
		]);
		/*
		$view         = View::getInstance();
		$view->set([
			'name'  => '',
			'type'  => 'folder',
			'path'  => '',
			'items' => $response,
		]);
		*/
		return;
	}
}
