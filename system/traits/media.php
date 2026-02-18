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
use function Vvveb\rrmdir;
use function Vvveb\sanitizeFileName;
use Vvveb\System\Event;

trait Media {
	public $uploadDenyExtensions = ['php', 'svg', 'js', 'exe'];

	public $uploadDenyMime    = ['image/svg', 'image/svg+xml', 'application/javascript', 'application/x-msdownload'];

	public $stripMetadataMime = ['image/jpg', 'image/jpeg', 'image/png', 'image/webp', 'image/avif'];

	//protected $uploadAllowExtensions = ['ico','jpg','jpeg','png','gif','webp', 'mp4', 'mkv', 'mov'];

	protected function dirForType($type) {
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

	protected function setMediaEndpoints($controllerPath) {
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

		list($files) = Event::trigger(__CLASS__, __FUNCTION__ , $files, $this);

		if ($files) {
			$length = count($files['name'] ?? []);

			for ($count = 0; $count < $length; $count++) {
				$path      = sanitizeFileName($this->request->post['mediaPath'] ?? '');
				$fileName  = sanitizeFileName($files['name'][$count]);

				if (V_SUBDIR_INSTALL && strpos($path, V_SUBDIR_INSTALL) === 0) {
					//$path  = str_replace(V_SUBDIR_INSTALL, '', $path);
					$path  = substr_replace($path, '', 0, strlen(V_SUBDIR_INSTALL));
				}

				$path      = preg_replace('@.*[\\\/]public[\\\/]media|.*[\\\/]media|.*[\\\/]public@', '', $path);
				$extension = strtolower(substr($fileName, strrpos($fileName, '.') + 1));
				$mimeType  = mime_content_type($files['tmp_name'][$count]);

				if ($files['error'][$count] == UPLOAD_ERR_OK) {
					$success = true;
				} else {
					$message = fileUploadErrMessage($files['error'][$count]);
				}

				if (isset($this->uploadDenyExtensions) && in_array($extension, $this->uploadDenyExtensions)) {
					$message .= __('File type not allowed!');
					$success = false;
				}

				if (isset($this->uploadDenyMime) && in_array($mimeType, $this->uploadDenyMime)) {
					$message .= __('File type not allowed!');
					$success = false;
				}

				if (isset($this->stripMetadataMime) && in_array($mimeType, $this->stripMetadataMime)) {
					$files['tmp_name'][$count];
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
		$file        = $this->request->post['file'];
		$message     = ['success' => false, 'message' => __('Error deleting file!')];
		$themeFolder = $this->dirMedia;

		if ($file) {
			if (is_array($file)) {
				foreach ($file as $f) {
					$f = sanitizeFileName($f);
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

	function rename() {
		$file        = sanitizeFileName($this->request->post['file']);
		$newfile     = sanitizeFileName($this->request->post['newfile'] ?? '');
		$newname     = sanitizeFileName($this->request->post['newname'] ?? '');
		$duplicate   =  $this->request->post['duplicate'] ?? false;
		$dirMedia    = $this->dirMedia;

		$currentFile = $dirMedia . DS . $file;
		if ($newfile) {
			$targetFile  = $dirMedia . DS . $newfile;
		}

		if ($newname) {
			$targetFile  = dirname($currentFile) . DS . $newname;
		}

		$extension = strtolower(substr($targetFile, strrpos($targetFile, '.') + 1));

		if (isset($this->uploadDenyExtensions) && in_array($extension, $this->uploadDenyExtensions)) {
			$message .= __('File type not allowed!');
			$success = false;
		}

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
