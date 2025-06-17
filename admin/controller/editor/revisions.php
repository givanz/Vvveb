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
use function Vvveb\friendlyDate;
use function Vvveb\sanitizeFileName;
use Vvveb\System\Sites;

class Revisions extends Base {
	function getThemeFolder() {
		$theme = $this->request->get['theme'] ?? Sites::getTheme() ?? 'default';

		return $theme;
	}

	private function sanitizeBackupFileName($fileName) {
		return str_replace(['.', '/', '\\'], '', $fileName);
	}

	private function backupFolder() {
		$theme = $this->getThemeFolder();

		return  DIR_THEMES . $theme . DS . 'backup' . DS;
	}

	function delete() {
		$file = $this->request->post['file'] ?? false;

		if ($file) {
			$file = $this->backupFolder() . $this->sanitizeBackupFileName($file) . '.html';

			$text    = __('Error deleting file!' . $file);
			$success = false;

			if (file_exists($file)) {
				$success = unlink($file);

				if ($success) {
					$text = __('File deleted!');
				}
			}

			$data = ['success' => $success, 'message' => $text];

			$this->response->setType('json');
			$this->response->output($data);
		}
	}

	function load() {
		$file  = $this->request->post['file'] ?? false;
		$theme = $this->getThemeFolder();

		if ($file) {
			$file = $this->backupFolder() . $this->sanitizeBackupFileName($file) . '.html';

			if (file_exists($file)) {
				$this->response->setType('text');
				$html = file_get_contents($file);
				$base = "/themes/$theme/";

				if (strpos($html, '<base') !== false) {
					$html = preg_replace('/<base(.*)href=["\'](.*?)["\'](.*?)>/', '<base$1href="' . $base . '"$3>', $html);
				} else {
					$html = str_replace('<head>', "<head>\n<base href=\"$base\">\n", $html);
				}

				$this->response->output($html);
			}
		} else {
			die(__('Invalid request!'));
		}
	}

	function revisions() {
		$theme        = $this->getThemeFolder();
		$template     = $this->request->get['template'] ?? false;
		$backupFolder = $this->backupFolder();
		$revisions    = [];

		if ($template) {
			$templateName = str_replace(['/', '.html'], ['-', ''], sanitizeFileName($template));
			$path         = '//' . $_SERVER['HTTP_HOST'] . PUBLIC_PATH . "/themes/$theme/";

			$glob     = glob("$backupFolder/$templateName@*.html");

			foreach ($glob as &$file) {
				$file              = basename($file, '.html');
				$url 			           = $path . "backup/$file.html";
				list($name, $date) = explode('@', $file);
				$date              = str_replace(['_', ';'], [' ', ':'], $date);
				$date_friendly     = friendlyDate($date);
				/*
				$time = date_parse_from_format($this->revisionDateFormat, $date);
				unset($time['warnings'], $time['warning_count'], $time['errors'], $time['error_count']);
				 */
				$revisions[$date] = compact(['url', 'file', 'date', 'date_friendly', 'name'/*, 'time'*/]);
			}

			krsort($revisions);

			//$revisions[0] = ['url' => $path . $template, 'file' => $template, '' => '', 'date_friendly' => 'Live', 'date' => '', 'name' => ''];
		}

		$this->response->setType('json');
		$this->response->output($revisions);
	}

	function index() {
	}
}
