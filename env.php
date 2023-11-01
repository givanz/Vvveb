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

/*
Check .sql files for changes and recompile, use on dev only
*/
define('SQL_CHECK', true);

/*
 Page cache needs web server support for maximum performance, make sure that apache has .htaccess support and nginx is configured according to included nginx.conf
 */
define('PAGE_CACHE', false);

/*
Disable on production to hide error messages, if enabled it will show detailed error messages 
Warning: Enabling debug will decrease performance
*/
defined('DEBUG') || define('DEBUG', true);
defined('VTPL_DEBUG') || define('VTPL_DEBUG', false);

/*
If enabled if a plugin generates an error it will be automatically disabled
*/
defined('DISABLE_PLUGIN_ON_ERORR') || define('DISABLE_PLUGIN_ON_ERORR', false);

//no trailing slash for subdir path
//defined('V_SUBDIR_INSTALL') || define('V_SUBDIR_INSTALL', '/vvveb');
defined('V_SUBDIR_INSTALL') || define('V_SUBDIR_INSTALL', false);

//if shared session is enabled then user session (login) will work on all subdomains on multisite installations
defined('V_SHARED_SESSION') || define('V_SHARED_SESSION', false);

defined('LOG_SQL_QUERIES') || define('LOG_SQL_QUERIES', false);

if (DEBUG) {
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
}
