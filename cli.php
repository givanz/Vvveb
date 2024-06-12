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

$msg = <<<MSG
Usage cli.php [app] [parameters...] 
where app can be admin, app or install and parameters are a list of name=value that will be used as Http GET/POST parameters when calling controllers.
You can call any module and action by passing the corresponding module and action parameters.
Note:For admin app super admin is used as user.

Examples: 

#disable plugin
php cli.php admin module=plugin/plugins action=deactivate plugin=markdown-import

#activate plugin
php cli.php admin module=plugin/plugins action=checkPluginAndActivate plugin=markdown-import

#delete post
php cli.php admin module=content/posts action=delete post_id=1

#fresh install MySQL
php cli.php install host=127.0.0.1 user=root password= database=vvveb admin[email]=admin@vvveb.com admin[password]=admin

#fresh install PgSQL
php cli.php install engine=pgsql host=127.0.0.1 user=postgres password= database=vvveb admin[email]=admin@vvveb.com admin[password]=admin

#fresh install SQLite
php cli.php install engine=sqlite admin[email]=admin@vvveb.com admin[password]=admin

#import markdown posts from folder /docs/user into site with id 5
php cli.php admin module=plugins/markdown-import/settings action=import site_id=5 settings[path]=/docs/user

#import markdown posts from folder /docs/developer into site with id 6
php cli.php admin module=plugins/markdown-import/settings action=import site_id=6 settings[path]=/docs/developer

#import content
php cli.php admin module=tools/import action=upload file[]=pages.xml file[]=menus.xml

php cli.php admin module=tools/import action=upload file[]='/home/www/vvveb/pages.xml' file[]='/home/www/vvveb/landing-menu-export.xml' file[]='/home/www/vvveb/plugins.xml' file[]='/home/www/vvveb/themes.xml' file[]='/home/www/vvveb/plugins-posts.xml' 

# run cron jobs
php cli.php app module=cron

# simulate a page request and get page json as output
php cli.php app request_uri=/hello-world
\n
MSG;

define('V_VERSION', '0.0.1');

function is_installed() {
	return file_exists(DIR_ROOT . 'config' . DS . 'db.php');
}

$params = implode('&', array_slice($argv, 2));
parse_str($params, $_GET);
parse_str($params, $_POST);

//simulate a page request
if (isset($_GET['request_uri'])) {
	$_SERVER['REQUEST_URI'] = $_GET['request_uri'];
} else {
	define('CLI',true);
}

$app    = 'app';
$appDir = '';

if (isset($argv[1])) {
	switch ($argv[1]) {
	case 'install':
		$app    = 'install';
		$appDir = 'install';

		break;

	case 'admin':
		$app    = 'admin';
		$appDir = 'admin';

		break;
	}
} else {
	die($msg);
}

define('DS', DIRECTORY_SEPARATOR);
define('DIR_ROOT', __DIR__ . DS);
define('DIR_SYSTEM', DIR_ROOT . 'system' . DS);
define('PUBLIC_PATH', DS . 'public' . DS);
defined('PAGE_CACHE_DIR') || define('PAGE_CACHE_DIR', 'page-cache' . DS);
define('PUBLIC_THEME_PATH', DS . 'public' . DS);

define('APP', $app);

//common constants
include DIR_ROOT . 'env.php';

include DIR_SYSTEM . '/core/startup.php';

function superAdminLogin() {
	$login =
	[
		'admin_id'       => 1,
		'username'       => 'admin',
		'email'          => 'cli@vvveb',
		'url'            => '',
		'registered'     => '',
		'token'			       => '',
		'status'         => 1,
		'display_name'   => 'Super Admin',
		'role_id'        => 1,
		'permissions'    => ['allow' => ['*'], 'deny' => ['']],
	];

	return session(['admin' => $login]);
}

if ($app == 'admin') {
	superAdminLogin();
}

System\Core\Response::getInstance()->setType('json');
System\Core\start();
