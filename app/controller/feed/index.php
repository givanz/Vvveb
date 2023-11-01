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

use \Vvveb\Controller\Base;
use Vvveb\System\Functions\Str;

class Index extends Base {
	function index() {
		$rss = $this->request->get['rss'] ?? false;

		if ($rss) {
			$rss = Str::sanitizeFilename($rss);
			header('Content-type: text/xml');

			return "/feed/{$rss}.xml";
		}
	}
}
