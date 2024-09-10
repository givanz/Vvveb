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

namespace Vvveb\Controller\Product;

use function Vvveb\__;
use Vvveb\Controller\Crud;
use function Vvveb\sanitizeFileName;
use Vvveb\System\Images;

class DigitalAsset extends Crud {
	protected $type = 'digital_asset';

	protected $controller = 'digital-asset';

	protected $module = 'product';

	function delete() {
		$file = sanitizeFileName($this->request->post['file']);
		$dir  = DIR_STORAGE . 'digital_assets';

		header('Content-type: application/json; charset=utf-8');

		if ($file && @unlink($dir . DS . $file)) {
			$message = ['success' => true, 'message' => __('File deleted!')];
		} else {
			$message = ['success' => false, 'message' => __('Error deleting file!')];
		}

		$this->response->setType('json');
		$this->response->output($message);
	}

	function upload() {
		$path      = sanitizeFileName($this->request->post['mediaPath']);
		$file      = $this->request->files['file'] ?? [];
		$fileName  = sanitizeFileName($file['name']);
		$path      = preg_replace('@^[\\\/]public[\\\/]media|^[\\\/]media|^[\\\/]public@', '', $path);
		$extension = strtolower(substr($fileName, strrpos($fileName, '.') + 1));
		$success   = false;
		$return    = '';
		$message   = '';
		$dirMedia  = DIR_STORAGE . 'digital_assets';

		if ($file) {
			if ($file['error'] == UPLOAD_ERR_OK) {
				$success = true;
			} else {
				$message = fileUploadErrMessage($file['error']);
			}

			/*
			if (in_array($extension, $this->uploadDenyExtensions)) {
				$message = __('File type not allowed!');
				$success = false;
			}
			 */

			$origFilename = $fileName;
			$i            = 1;

			if ($success) {
				while (file_exists($destination = $dirMedia . $path . DS . $fileName) && ($i++ < 5)) {
					$fileName = rand(0, 10000) . '-' . $origFilename;
				}

				if (move_uploaded_file($file['tmp_name'], $destination)) {
					if (isset($this->request->post['onlyFilename'])) {
						$return = $fileName;
					} else {
						$return = $destination;
					}
					$message = __('File uploaded successfully!');
				} else {
					$destination = $dirMedia . $path . DS;
					$success     = false;

					if (! is_writable($destination)) {
						$message = sprintf(__('%s not writable!'), $destination);
					} else {
						$message = __('Error moving uploaded file!');
					}
				}
			}
		} else {
			$message = __('Invalid upload!');
		}

		$message = ['success' => $success, 'message' => $message, 'file' => $return];

		$this->response->setType('json');
		$this->response->output($message);
	}

	function scan() {
		$type          = $this->request->get['type'] ?? 'public';
		$scandir       = DIR_STORAGE . 'digital_assets';

		if (! $scandir) {
			return [];
		}

		// This function scans the files folder recursively, and builds a large array
		$scan = function ($dir) use ($scandir, &$scan) {
			$files = [];

			// Is there actually such a folder/file?

			if (file_exists($dir)) {
				$listdir = @scandir($dir);

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
	}

	function index() {
		parent::index();

		$admin_path = \Vvveb\adminPath();

		$controllerPath        = $admin_path . 'index.php?module=product/digital-asset';
		$this->view->scanUrl   = "$controllerPath&action=scan";
		$this->view->uploadUrl = "$controllerPath&action=upload";
		$this->view->deleteUrl = "$controllerPath&action=delete";

		if ($this->view->digital_asset) {
			$this->view->digital_asset['image']     = $this->view->digital_asset['file'];
			$this->view->digital_asset['image_url'] = Images::image($this->view->digital_asset['image'], 'digital_asset', 'thumb');
		}
	}
}
