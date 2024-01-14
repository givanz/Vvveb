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

namespace Vvveb\Controller\Feed;

use Vvveb\Controller\Base;

class Robots extends Base {
	function index() {
		$text   = '';
		$robots = DIR_PUBLIC . 'vrobots.txt';

		if (file_exists($robots)) {
			$text = @file_get_contents($robots);
		}

		$host = ' https://' . $_SERVER['HTTP_HOST'] ?? '' . (V_SUBDIR_INSTALL ? V_SUBDIR_INSTALL : '');

		// change sitemap urls to absolute
		if ($host) {
			$text = preg_replace('@(sitemap):\s+/@', "$1: $host/", $text);
		}

		$this->response->setType('text');
		$this->response->output($text);
	}
}
