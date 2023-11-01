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

class ErrorLog extends Base {
	function download() {
		$filename = ini_get('error_log');

		if (is_file($filename)) {
			$fp = fopen($filename, 'rb');

			header('Content-Description: File Transfer');
			//header('Content-Type: application/octet-stream');
			header('Content-Type: text/plain');
			header('Content-Disposition: attachment; filename="' . basename($filename) . '"');
			header('Expires: 0');
			header('Cache-Control: must-revalidate');
			header('Pragma: public');
			header('Content-Length: ' . filesize($filename));

			fpassthru($fp);

			exit;
		} else {
			$this->view->errors[] = __('Error downloading log!');

			return $this->index();
		}
	}

	function clear() {
		$filename = ini_get('error_log');

		if (is_file($filename)) {
			@$handle = fopen($filename, 'r+');

			if ($handle) {
				if (ftruncate($handle, 0)) {
					$this->view->success[] = __('Log cleared!');
				} else {
					$this->view->errors[] = __('Error clearing log!');
				}
				fclose($handle);
			} else {
				$this->view->errors[] = __('Error clearing log!');
			}
		} else {
			$this->view->errors[] = __('Log not a valid file!');
		}

		return $this->index();
	}

	function index() {
		$count       = 100;
		$error_log   = ini_get('error_log');
		$is_readable = null;
		$text        = null;

		if (! empty($error_log)) {
			$is_readable = is_readable($error_log);

			if ($is_readable) {
				$text = \Vvveb\tail($error_log, $count);
			}
		} else {
			$error_log = __('empty file');
		}

		$log['count']    = $count;
		$log['log']      = $error_log;
		$log['text']     = $text ?? __('PHP error log not readable, make sure that your log is properly configured and that is readable.');
		$log['readable'] = $is_readable;
		$this->view->log = $log;
	}
}
