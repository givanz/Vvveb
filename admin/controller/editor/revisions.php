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

use Vvveb\Controller\Base;
use function Vvveb\friendlyDate;
use function Vvveb\sanitizeFileName;
use Vvveb\System\Sites;

class Revisions extends Base {
	private function backupFolder() {
		$theme = $this->request->get['theme'] ?? Sites::getTheme() ?? 'default';

		return  DIR_THEMES . DS . $theme . DS . 'backup' . DS;
	}

	function delete() {
	}

	function load() {
		$template   = $this->request->get['template'] ?? false;

		$this->view->text = $text;
		$this->response->setType('text');
	}

	function preview() {
	}

	function revisions() {
		$template     = $this->request->get['template'] ?? false;
		$backupFolder = $this->backupFolder();
		$revisions    = [];

		if ($template) {
			$template     = str_replace(['/', '.html'], ['-', ''], sanitizeFileName($template));

			$glob  = glob("$backupFolder/$template|*.html");

			foreach ($glob as &$file) {
				$file              = basename($file, '.html');
				list($name, $date) = explode('|', $file);
				$date              = str_replace('_', ' ', $date);
				$date_friendly     = friendlyDate($date);
				/*
				$time = date_parse_from_format($this->revisionDateFormat, $date);
				unset($time['warnings'], $time['warning_count'], $time['errors'], $time['error_count']);
				 */
				$revisions[] = compact(['file', 'date', 'date_friendly', 'name'/*, 'time'*/]);
			}
		}

		$this->response->setType('json');
		$this->response->output($revisions);
	}

	function index() {
	}
}
