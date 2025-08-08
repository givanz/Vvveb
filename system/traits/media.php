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
use function Vvveb\parseQuantity;
use function Vvveb\sanitizeFileName;

trait Media {
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

	function setMediaEndpoints($controllerPath) {
		$this->view->mediaUrl          = $controllerPath;
		$this->view->scanUrl           = "$controllerPath&action=scan";
		$this->view->uploadUrl         = "$controllerPath&action=upload";
		$this->view->deleteUrl         = "$controllerPath&action=delete";
		$this->view->renameUrl         = "$controllerPath&action=rename";
		$this->view->uploadMaxFilesize = parseQuantity(ini_get('upload_max_filesize'));
		$this->view->postMaxSize       = parseQuantity(ini_get('post_max_size'));
	}

	function upload() {
		$files      = $this->request->files['files'] ?? [];
		$overwrite  = $this->request->post['overwrite'] ?? false;
		$success    = false;
		$return     = '';
		$message    = '';
		$response   = [];

		if ($files) {
			$length = count($files['name'] ?? []);

			for ($count = 0; $count < $length; $count++) {
				$path      = sanitizeFileName($this->request->post['mediaPath'] ?? '');
				$fileName  = sanitizeFileName($files['name'][$count]);

				if (V_SUBDIR_INSTALL && strpos($path, V_SUBDIR_INSTALL) === 0) {
					$path  = substr_replace($path, '', 0, strlen(V_SUBDIR_INSTALL));
				}

				$path      = preg_replace('@.*[\\\/]public[\\\/]media|.*[\\\/]media|.*[\\\/]public@', '', $path);
				$extension = strtolower(substr($fileName, strrpos($fileName, '.') + 1));

				if ($files['error'][$count] == UPLOAD_ERR_OK) {
					$success = true;
				} else {
					$message = fileUploadErrMessage($files['error'][$count]);
				}

				if (isset($this->uploadDenyExtensions) && in_array($extension, $this->uploadDenyExtensions)) {
					$message .= __('File type not allowed!');
					$success = false;
				}

				$origFilename = $fileName;
				$i            = 1;

				if ($success) {
					if ($overwrite) {
						$destination = $this->dirMedia . $path . DS . $fileName;
					} else {
						while (file_exists($destination = $this->dirMedia . $path . DS . $fileName) && ($i++ < 5)) {
							$fileName = rand(0, 10000) . '-' . $origFilename;
						}
					}

					if (@move_uploaded_file($files['tmp_name'][$count], $destination)) {
						if (isset($this->request->post['onlyFilename'])) {
							$return = $fileName;
						} else {
							$return = $destination;
						}
						$message = __('File uploaded successfully!');
					} else {
						$destination = $this->dirMedia . $path . DS;
						$success     = false;

						if (! is_writable($destination)) {
							$message = sprintf(__('%s not writable!'), $destination);
						} else {
							$message = __('Error moving uploaded file!');
						}
					}
				}

				$response[] = ['success' => $success, 'message' => $message, 'file' => $return, 'size' => $files['size'][$count]];
			}
		} else {
			$message    = __('Invalid upload!');
			$response[] = ['success' => $success, 'message' => $message, 'file' => $return];
		}

		$this->response->setType('json');
		$this->response->output($response);
	}

	function delete() {
		$file        = sanitizeFileName($this->request->post['file']);
		$themeFolder = $this->dirMedia;

		if ($file && @unlink($themeFolder . DS . $file)) {
			$message = ['success' => true, 'message' => __('File deleted!')];
		} else {
			$message = ['success' => false, 'message' => __('Error deleting file!')];
		}

		$this->response->setType('json');
		$this->response->output($message);
	}

	function rename() {
		$file        = sanitizeFileName($this->request->post['file']);
		$newfile     = sanitizeFileName($this->request->post['newfile']);
		$duplicate   =  $this->request->post['duplicate'] ?? false;
		$dirMedia    = $this->dirMedia;

		$currentFile = $dirMedia . DS . $file;
		$targetFile  = $dirMedia . DS . $newfile;

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

		$this->response->setType('json');
		$this->response->output($message);
	}

	function newFolder() {
		$folder  = sanitizeFileName($this->request->post['folder']);
		$path    = sanitizeFileName($this->request->post['path']);
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

		$message = ['success' => $success, 'message' => $message];

		$this->response->setType('json');
		$this->response->output($message);
	}

	function scan() {
		$scandir       = $this->dirMedia; //$this->dirForType($type);

		if (isset($this->dirMediaType) && $this->dirMediaType) {
			$type    = $this->request->get['type'] ?? 'public';
			$scandir = $this->dirForType($type);
		}

		if (! $scandir) {
			return [];
		}

		// This function scans the files folder recursively, and builds a large array
		$scan = function ($dir) use ($scandir, &$scan) {
			$files = [];

			// Is there actually such a folder/file?

			if (file_exists($dir)) {
				$listdir = @\scandir($dir);

				if ($listdir) {
					foreach ($listdir as $f) {
						if (! $f || $f[0] == '.' || $f == 'node_modules' || $f == 'vendor') {
							continue; // Ignore hidden files
						}

						if (is_dir($dir . DS . $f)) {
							// The path is a folder

							$files[] = [
								'name'  => $f,
								'type'  => 'folder',
								'path'  => str_replace([$scandir, '\\'], ['', '/'], $dir) . '/' . $f,
								'items' => $scan("$dir/$f"), // Recursively get the contents of the folder
							];
						} else {
							// It is a file

							$files[] = [
								'name' => $f,
								'type' => 'file',
								'path' => str_replace([$scandir, '\\'], ['', '/'], $dir) . '/' . $f,
								'size' => filesize("$dir/$f"), // Gets the size of this file
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
	}
}
