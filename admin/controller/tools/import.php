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
use Vvveb\System\Import\Sql;
use Vvveb\System\Import\Xml;

#[\AllowDynamicProperties]
class Import extends Base {
	function __construct() {
		$this->xml = new Xml();
		$this->sql = new Sql();
	}

	function importFile($file, $name = '') {
		$result = false;

		if ($file) {
			try {
				// use temorary file, php cleans temporary files on request finish.
				$result = $this->import($file);
			} catch (\Exception $e) {
				$error                = $e->getMessage();
				$this->view->errors[] = $error;
			}
		}

		if ($result) {
			$successMessage          = sprintf(__('Import `%s` was successful!'), $name);
			$this->view->success[]   = $successMessage;
		} else {
			$errorMessage           = sprintf(__('Failed to import `%s` file!'), $name);
			$this->view->errors[]   = $errorMessage;
		}
	}

	function upload() {
		$files = $this->request->files;

		//check for uploaded files
		if ($files) {
			foreach ($files as $file) {
				$this->importFile($file['tmp_name'], $file['name']);
			}
		}

		//check if filename is given (from cli)
		$file = $this->request->post['file'] ?? false;

		if (is_array($file)) {
			foreach ($file as $f) {
				$this->importFile($f, basename($f));
			}
		} else {
			if ($file) {
				$this->importFile($file, basename($file));
			}
		}

		return $this->index();
	}

	private function import($file) {
		$extension = substr($file, -3, 3);

		$result = false;

		if ($extension == 'xml') {
			$content = file_get_contents($file);
			$result  = $this->xml->import($content);
		}

		if ($extension == 'sql') {
			$content = file_get_contents($file);
			$result  = $this->sql->multiQuery($content, $file);
		}

		return $result;
	}

	function index() {
		//$this->import();
	}
}
