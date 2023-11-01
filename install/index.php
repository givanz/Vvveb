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

namespace Vvveb;

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
define('DEBUG', true);

define('APP', 'install');

if (! defined('PUBLIC_PATH')) {
	define('PUBLIC_PATH', '/public/');
	define('PUBLIC_THEME_PATH', '/public/install/');
}

define('CRITICAL_EXTENSIONS', ['xml', 'libxml', 'dom', 'pcre']);

$extensions = '';

foreach (CRITICAL_EXTENSIONS as $extension) {
	if (! extension_loaded($extension)) {
		$extensions .= sprintf('Required PHP extension <b>%s</b> is not installed', $extension);
	}
}

if ($extensions) {
	echo "<div style='text-align:center;margin:2rem;'>$extensions</div>";
}

return include __DIR__ . '/../index.php';
