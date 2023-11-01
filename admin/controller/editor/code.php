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

namespace Vvveb\Controller\Editor;

use function Vvveb\__;
use Vvveb\Controller\Base;
use function Vvveb\sanitizeFileName;
use Vvveb\System\Core\View;

class Code extends Base {
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
		$type                = $this->request->get['type'];
		$admin_path          = \Vvveb\adminPath();
		$controllerPath      = $admin_path . 'index.php?module=editor/code';

		$this->view->scanUrl       = "$controllerPath&action=scan&type=$type";
		$this->view->uploadUrl     = "$controllerPath&action=upload&type=$type";
		$this->view->saveUrl       = "$controllerPath&action=save&type=$type";
		$this->view->loadFileUrl   = "$controllerPath&action=loadFile&type=$type";
		$this->view->saveUrl       = "$controllerPath&action=save&type=$type";
		$this->view->type          = $type;

		if ($type) {
			$this->view->mediaPath   = str_replace('/media', "/$type", $this->view->mediaPath);
		}
	}

	function sanitizeFileName($file, $type) {
		if ($type == 'plugins') {
			$file = DIR_PLUGINS . preg_replace("@^[\/]plugins[\/]@", '', $file);
		} else {
			if ($type == 'themes') {
				$file = DIR_THEMES . $file;
			} else {
				$file = DIR_PUBLIC . $file;
			}
		}

		$file = sanitizeFileName($file);

		return $file;
	}

	function save() {
		$type    = $this->request->get['type'];
		$file    = $this->request->get['file'];
		$content = $this->request->post['content'];
		$file    = $this->sanitizeFileName($file, $type);

		$message = ['success' => false, 'message' => sprintf(__('Error saving: %s!'), $file)];

		if (! is_writable($file)) {
			$message = ['success' => false, 'message' => sprintf(__('File not writable: %s Check if file has write permission.'), $file)];
		} else {
			if (file_put_contents($file, $content)) {
				$message = ['success' => true, 'message' => __('File saved!')];
			}
		}

		header('Content-type: application/json; charset=utf-8');
		echo json_encode($message);

		die();
	}

	function loadFile() {
		$type = $this->request->get['type'];
		$file = $this->request->get['file'];
		$file = $this->sanitizeFileName($file, $type);

		if (! is_readable($file)) {
			die("File not readable: $file");
		}

		if ($content = file_get_contents($file)) {
			die($content);
		}

		die("Error loading: $file");
	}

	function upload() {
		$type = $this->request->post['type'];
		$path = sanitizeFileName($this->request->post['mediaPath']);
		$file = sanitizeFileName($this->request->files['file']['name']);
		$path = str_replace('/media', '', $path);

		$destination = DIR_MEDIA . $path . '/' . $file;

		if (move_uploaded_file($this->request->files['file']['tmp_name'], $destination)) {
			if (isset($this->request->post['onlyFilename'])) {
				echo $file;
			} else {
				echo $destination;
			}
		} else {
			echo __('Error uploading file!');
		}

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
		$view         = View::getInstance();
		$view->noJson = true;

		header('Content-type: application/json');

		echo json_encode([
			'name'  => '',
			'type'  => 'folder',
			'path'  => '',
			'items' => $response,
		]);

		die();

		return false;
	}
}
