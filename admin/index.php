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

define('APP', 'admin');
define('DISABLE_PLUGIN_ON_ERORR', true);
//no trailing slash for subdir path
//define('V_SUBDIR_INSTALL', '/vvveb');

if (! isset($PUBLIC_PATH)) {
	$PUBLIC_PATH = '/public/';
}

if (! isset($PUBLIC_THEME_PATH)) {
	$PUBLIC_THEME_PATH = '/public/admin/';
}
/*
if (! defined('PUBLIC_PATH')) {
	define('PUBLIC_PATH', (V_SUBDIR_INSTALL ? V_SUBDIR_INSTALL : '') . $PUBLIC_PATH);
	define('PUBLIC_THEME_PATH', (V_SUBDIR_INSTALL ? V_SUBDIR_INSTALL : '') . $PUBLIC_THEME_PATH);
}
*/
return include __DIR__ . '/../index.php';
