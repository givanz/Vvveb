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

namespace Vvveb\System\Core;

use function Vvveb\camelToUnderscore;
use Vvveb\System\Extensions\Plugins;
use Vvveb\System\Session;
use Vvveb\System\Sites;
use Vvveb\System\Sqlp\Sqlp;

$storage_dir = DIR_ROOT . 'storage' . DS;

if (is_writable($storage_dir)) {
	define('DIR_STORAGE', $storage_dir);
} else {
	$storage_dir = sys_get_temp_dir() . DS . 'storage' . DS;

	if (! is_dir($storage_dir)) {
		@mkdir($storage_dir);
		@mkdir($storage_dir . 'compiled-templates' . DS);
		@mkdir($storage_dir . 'cache');
		@mkdir($storage_dir . 'model');
		@mkdir($storage_dir . join(DS, ['model', 'admin']) . DS);
		@mkdir($storage_dir . join(DS, ['model/app']) . DS);
		@mkdir($storage_dir . join(DS, ['model/install']) . DS);
	}

	define('DIR_STORAGE', $storage_dir);
}

define('DIR_CACHE', DIR_ROOT . join(DS, ['storage', 'cache']) . DS);
define('DIR_PLUGINS', DIR_ROOT . 'plugins' . DS);
define('DIR_COMPILED_TEMPLATES', DIR_STORAGE . 'compiled-templates' . DS);
define('DIR_BACKUP', DIR_STORAGE . 'backup' . DS);
define('DIR_THEMES', DIR_ROOT . join(DS, ['public', 'themes']) . DS);
define('DIR_PUBLIC', DIR_ROOT . 'public' . DS);

if (APP == 'app') {
	define('DIR_THEME', DIR_ROOT . join(DS, ['public', 'themes']) . DS);
} else {
	define('DIR_THEME', DIR_ROOT . 'public' . DS . APP . DS);
}

define('DIR_APP', DIR_ROOT . APP . DS);
define('DIR_TEMPLATE', DIR_APP . 'template' . DS);
define('DIR_MEDIA', DIR_PUBLIC . 'media' . DS);
define('CDATA_START', '<![CDATA[');
define('CDATA_END', ']]>');

$error_log = ini_get('error_log');

if (! $error_log || $error_log == '/dev/null') {
	ini_set('error_log', $storage_dir . 'logs/error_log');
}

include DIR_SYSTEM . 'session.php';

include DIR_SYSTEM . '/component/component.php';

require_once DIR_SYSTEM . '/core/frontcontroller.php';

require_once DIR_SYSTEM . '/core/view.php';

require_once DIR_SYSTEM . '/functions.php';

require_once DIR_SYSTEM . 'event.php';

function logError($message) {
	return error_log($message);
}

function regenerateSQL($sqlFile, $file, $modelName, $namespace) {
	$sqlp = new Sqlp();

	$sqlp->parseSqlPfile($sqlFile, $modelName, $namespace);

	$dir = dirname($file);

	if (! file_exists($dir)) {
		@mkdir($dir,0755,true);
	}
	file_put_contents($file, "<?php \n" . $sqlp->generateModel());
}

function autoload($class) {
	// project-specific namespace prefix
	$prefix = 'Vvveb\\';

	// does the class use the namespace prefix?
	$len = strlen($prefix);

	if (strncmp($prefix, $class, $len) !== 0) {
		// no, move to the next registered autoloader
		return;
	}

	$relativeClass = substr($class, $len);
	$isPlugin      = (strncmp($relativeClass, 'Plugins\\', 7) === 0);
	// replace the namespace prefix with the base directory, replace namespace
	// separators with directory separators in the relative class name, append
	// with .inc
	$root = DIR_APP;

	//if namespace is Vvveb\System or Vvveb\Plugins load from root dir above app dir
	if ((strncmp($relativeClass, 'System\\', 7) === 0) ||
		$isPlugin ||
		(strncmp($relativeClass, 'Sql\Plugins\\', 11) === 0)) {
		$root = DIR_ROOT;
	}

	//if namespace is App change to app dir
	if ((strncmp($relativeClass, 'App\\', 4) === 0)) {
		$root = DIR_ROOT . 'app' . DS;
	}

	//if namespace is Admin change to admin dir
	if ((strncmp($relativeClass, 'Admin\\', 6) === 0)) {
		$root = DIR_ROOT . 'admin' . DS;
	}

	$isSql      = (substr_compare($relativeClass, 'SQL', -3, 3) === 0);

	//check if sql files are changed or missing to regenerate sql class
	if ($isSql) {
		$isFromPlugin = strpos($relativeClass, 'Plugins\\');
		$sqlFile      = str_replace(['Sql', '\\'], ['', DS], substr($relativeClass, 0, -3));
		//$sqlFile = strtolower(preg_replace('/(?<!^)(?<!\/)[A-Z]/', '-$0', $sqlFile));

		if ($isFromPlugin > 0) {
			//convert camelCase to snake_case
			$sqlFile    = strtolower(preg_replace('/(?<!^)(?<!\/)(?<!\\\)[A-Z]/', '-$0', $sqlFile));
			$file       = basename($sqlFile);
			$pluginName = str_replace([DS . 'plugins' . DS, DS . $file], '', $sqlFile) . DS;
			$pluginName = strtolower(preg_replace('/(?<!^)(?<!\/)(?<!\\\)[A-Z]/', '-$0', $pluginName));
			$sqlFile    = DIR_PLUGINS . $pluginName . 'sql' . DS . DB_ENGINE . DS . $file . '.sql';
		} else {
			$sqlFile   = strtolower($sqlFile) . '.sql';
			//$sqlFile   = DIR_SQL . $sqlFile . '.sql';
		}

		$name      = str_replace(['\\', 'sql' . DS], [DS, ''], strtolower($relativeClass));
		$modelName = ucwords(basename(str_replace('sql', '', $name)));
		$namespace = ucwords(dirname($name));

		if ($namespace != '.') {
			$namespace = str_replace('/', '\\', "\\$namespace");
		} else {
			$namespace = '';
		}

		$file       = DIR_STORAGE . 'model' . DS . APP . DS . $name . '.' . DB_ENGINE . '.php';
		$fileExists = file_exists($file);

		if (SQL_CHECK || ! $fileExists) {
			$sqlExists = false;

			if ($isFromPlugin > 0) {
				$fullSqlFile = $sqlFile;
				$sqlExists   = file_exists($fullSqlFile);
			} else {
				//fallback to admin if sql file is missing in APP
				foreach ([APP, 'admin'] as $app) {
					$fullSqlFile = DIR_ROOT . $app . DS . 'sql' . DS . DB_ENGINE . DS . $sqlFile;

					if (file_exists($fullSqlFile)) {
						$sqlExists = true;

						break;
					}
				}
			}

			if (! $sqlExists) {
				throw new \Exception(sprintf(\Vvveb\__('SQL file %s does not exist for %s!'), $sqlFile, $relativeClass));
			}
			//if the file has not been generated yet or sql files is changed recompile
			if (! $fileExists || ((filemtime($fullSqlFile) > filemtime($file)))) {
				regenerateSQL($fullSqlFile, $file, $modelName, $namespace);
				$fileExists = true;
			}
		}

		if ($fileExists) {
			require_once $file;
		}
	} else {
		$file       = $root . str_replace('\\', '/', camelToUnderscore($relativeClass)) . '.php';

		if ($isPlugin && ($isController = strpos($relativeClass, 'Controller\\'))) {
			$file= str_replace('/controller/', '/' . APP . '/controller/', $file);
		}

		$fileExists = file_exists($file);

		if ($fileExists) {
			require_once $file;
		}
	}
}

function autoload_vendor($class) {
	$path = str_replace('\\', DS, $class);

	$file = DIR_ROOT . 'vendor' . DS . "$path.php";

	// if the file exists, require it
	if (file_exists($file)) {
		require_once $file;
	}
}

function exceptionToArray($exception, $file = false) {
	$file   = $exception->getFile() ? $exception->getFile() : $file;
	$lineNo = $exception->getLine() - 1;
	//$code = $exception->getCode();
	$class     = get_class($exception);
	$lines     = [];
	$codeLines = [];
	$line      = $lineNo;
	$code      = '';

	if ($file && file_exists($file) && ($codeLines = file($file)) && isset($codeLines[$lineNo])) {
		$codeLines[$lineNo] = preg_replace("/\n$/","\t // <==\n", $codeLines[$lineNo]);
		$lines              = array_slice($codeLines, $lineNo - 7, 14);
		$line               = implode("\n", array_slice($codeLines, $lineNo, 1));
		$before             = implode("\n", array_slice($codeLines, $lineNo - 6, 5));
		$after              = implode("\n", array_slice($codeLines, $lineNo + 1, 5));
		$code               = "$before<b>$line</b>$after";
	}

	$message = [
		'message' => $exception->getMessage(),
		'file'    => $file,
		'line_no' => $lineNo,
		'line'    => $line,
		'lines'   => $lines,
		'trace'   => $exception->getTraceAsString(),
		'code'    => $codeLines,
	];

	return $message;
}

function exceptionHandler($exception) {
	$message = exceptionToArray($exception, $exception->getFile());

	//check if error is generated by a plugin and disable it
	pluginErrorCheck($message['file']);

	return FrontController::notFound(false, $message, 500);
	//pluginErrorCheck($file);
	echo '<b>Exception:</b><pre>';
	print_r($message);
	echo '</pre>';
}

function vErrorHandler($errno, $errstr, $errfile, $errline) {
	if (! (error_reporting() && $errno)) {
		// This error code is not included in error_reporting, so let it fall
		// through to the standard PHP error handler
		return false;
	}
	// $errstr may need to be escaped:
	$errstr = htmlspecialchars($errstr);

	switch ($errno) {
		case E_USER_WARNING:
		case E_WARNING:
			/*
			throw new \Exception($errstr, $errno);
			//throw new \Exception($errstr, $errno, 0, $errfile, $errline);
			return true;
			break;
			 */

		case E_USER_NOTICE:
			break;

		case E_ERROR:
		case E_USER_ERROR:
			/*
			echo "<b>ERROR</b> [$errno] $errstr<br />\n";
			echo "  Fatal error on line $errline in file $errfile";
			echo ', PHP ' . PHP_VERSION . ' (' . PHP_OS . ")<br />\n";
			 */
			//check if error is generated by a plugin and disable it
			pluginErrorCheck($errfile);

			throw new \ErrorException($errstr, $errno, 0, $errfile, $errline);

			return true;
			//return FrontController::notFound(false, $errstr, 500);
	}

	/* Don't execute PHP internal error handler */
	return false;
}

function pluginErrorCheck($file) {
	if (($pos = strpos($file, DIR_PLUGINS)) !== false) {
		$plugin = \Vvveb\filter('@([^/]+)@', str_replace(DIR_PLUGINS, '', $file));
		logError("'$plugin' plugin triggers fatal error.");

		if (DISABLE_PLUGIN_ON_ERORR) {
			logError(sprintf(\Vvveb\__('Disabling "%s" plugin.'), $plugin));
			Plugins::deactivate($plugin);
		}
	}
}

function fatalErrorHandler() {
	$message = error_get_last();

	if ($message) {
		vErrorHandler($message['type'], $message['message'],$message['file'], $message['line']);
	}
}

spl_autoload_register('Vvveb\System\Core\autoload');
//spl_autoload_register('Vvveb\System\Core\autoload_vendor');
set_exception_handler('Vvveb\System\Core\exceptionHandler');
set_error_handler('Vvveb\System\Core\vErrorHandler');
register_shutdown_function('Vvveb\System\Core\fatalErrorHandler');

require DIR_ROOT . '/vendor/autoload.php';

$dbDefault  = \Vvveb\config('db.default', 'default');
$connection = \Vvveb\config('db.connections.' . $dbDefault,  []);

if ($connection) {
	// Define default database configuration
	define('DB_ENGINE', $connection['engine']);
	define('DB_HOST',   $connection['host'] ?? '');
	define('DB_USER',   $connection['user'] ?? '');
	define('DB_PASS',   $connection['password'] ?? '');
	define('DB_NAME',   $connection['database'] ?? '');
	define('DB_PREFIX', $connection['prefix'] ?? '');
	define('DB_PORT',   $connection['port'] ?? null);
} else {/*
	define('DB_ENGINE', 'mysqli');
	define('DB_HOST', 'localhost');
	define('DB_USER', 'root');
	define('DB_PASS', '');
	define('DB_NAME', 'vvveb');
	define('DB_PREFIX', '');
	 */
}

if (defined('DB_ENGINE')) {
	define('DIR_SQL', DIR_APP . 'sql' . DS . DB_ENGINE . DS);
}

function start() {
	//start session
	//Session :: getInstance();
	$site = Sites :: getSiteData();

	if ($site) {
		define('SITE_URL', $site['host']);
		define('SITE_ID', $site['id'] ?? 1);

		//load plugins first for APP
		if (APP != 'admin') {
			Plugins :: loadPlugins(SITE_ID);
		}

		FrontController::dispatch();
	} else {
		define('SITE_URL', $_SERVER['HTTP_HOST'] ?? 'localhost');
		define('SITE_ID', 1);
		FrontController::notFound(false, 'Website not found!');
	}
}
