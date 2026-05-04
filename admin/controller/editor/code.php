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
use Vvveb\System\Traits\Media as MediaTrait;
use Vvveb\System\User\Admin;

class Code extends Base {
	use MediaTrait;

	protected $saveDenyExtensions = ['php', 'tpl', 'svg', 'js', 'exe', 'html', 'phtml', 'htaccess', 'phar'];

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

	function init() {
		$type           = $this->request->get['type'] ?? false;
		$this->dirMedia = $this->dirForType($type);

		return parent::init();
	}

	function index() {
		$type           = $this->request->get['type'] ?? false;
		$admin_path     = \Vvveb\adminPath();
		$controllerPath = $admin_path . 'index.php?module=editor/code';

		if (! $type) {
			return;
		}

		$this->setMediaEndpoints($controllerPath, "&type=$type");

		$this->view->loadFileUrl = "$controllerPath&action=loadFile&type=$type";
		$this->view->saveUrl     = "$controllerPath&action=save&type=$type";
		$this->view->type        = $type;

		if ($type) {
			$this->view->mediaPath   = str_replace('/media', "/$type", $this->view->mediaPath);
		}
	}

	function sanitizeFileName($file, $type) {
		if (V_SUBDIR_INSTALL && strpos($file, V_SUBDIR_INSTALL) === 0) {
			//$path  = str_replace(V_SUBDIR_INSTALL, '', $path);
			$file  = substr_replace($file, '', 0, strlen(V_SUBDIR_INSTALL));
		}

		$file = preg_replace("@^[\/]public@", '', $file);

		if ($type == 'plugins') {
			$dir  = DIR_PLUGINS;
			$file = preg_replace("@^[\/]plugins[\/]@", '', $file);
		} else {
			if ($type == 'themes') {
				$dir  = DIR_THEMES;
				$file = preg_replace("@^[\/]themes[\/]@", '', $file);
			} else {
				$dir  = DIR_PUBLIC;
			}
		}

		$file = $dir . $file;
		$file = sanitizeFileName($file);

		if (strncmp(realpath($file), $dir, strlen($dir)) !== 0) {
			return false;
		}

		return $file;
	}

	function save() {
		$type     = $this->request->get['type'];
		$filename = $this->request->get['file'];
		$content  = $this->request->post['content'];
		$file     = $this->sanitizeFileName($filename, $type);

		$success = false;
		$message = sprintf(__('Error saving: %s!'), $filename);

		if ($file) {
			$extension = strtolower(substr($file, strrpos($file, '.') + 1));

			if (in_array($extension, $this->saveDenyExtensions) && (($admin = Admin::current()) && ! in_array($admin['role'], ['super_admin', 'admin']))) {
				$message = sprintf(__('Saving not allowed for file type %s for this role!'), trim($extension, '.'));
				$success = false;
			} else {
				if ($extension == 'php' || $this->isExecutableFile($file)) {
					$message .= sprintf(__('File type %s not allowed!'), 'PHP');
					$success = false;
				} else {
					if (! is_writable($file)) {
						$message = sprintf(__('File not writable: %s Check if file has write permission.'), $filename);
						$success = false;
					} else {
						if (file_put_contents($file, $content)) {
							$message = __('File saved!');
							$success = true;
						}
					}
				}
			}
		}

		$output = ['success' => $success, 'message' => $message];
		$this->response->setType('json');
		$this->response->output($output);
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
								'path'  => str_replace([$scandir, '\\'], ['', '/'], $dir) . '/' . $f,
								'items' => $scan($dir . DS . $f), // Recursively get the contents of the folder
							];
						} else {
							// It is a file

							$files[] = [
								'name' => $f,
								'type' => 'file',
								'path' => str_replace([$scandir, '\\'], ['', '/'], $dir) . '/' . $f,
								'size' => @filesize($dir . DS . $f), // Gets the size of this file
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
