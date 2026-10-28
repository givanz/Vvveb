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
use Vvveb\System\Sites;
use Vvveb\System\Sqlp\Sqlp;

$storage_dir = DIR_ROOT . 'storage' . DS;

if (is_writable($storage_dir)) {
	define('DIR_STORAGE', $storage_dir);
} else {
	$storage_dir = sys_get_temp_dir() . DS . 'storage' . DS;

	if (!is_dir($storage_dir)) {
		@mkdir($storage_dir);
		@mkdir($storage_dir . 'compiled-templates' . DS);
		@mkdir($storage_dir . 'cache');
		@mkdir($storage_dir . 'model');
		@mkdir($storage_dir . implode(DS, ['model', 'admin']) . DS);
		@mkdir($storage_dir . implode(DS, ['model/app']) . DS);
		@mkdir($storage_dir . implode(DS, ['model/install']) . DS);
	}

	define('DIR_STORAGE', $storage_dir);
}

define('DIR_CACHE', DIR_ROOT . implode(DS, ['storage', 'cache']) . DS);
define('DIR_PLUGINS', DIR_ROOT . 'plugins' . DS);
define('DIR_COMPILED_TEMPLATES', DIR_STORAGE . 'compiled-templates' . DS);
define('DIR_BACKUP', DIR_STORAGE . 'backup' . DS);
define('DIR_THEMES', DIR_ROOT . implode(DS, ['public', 'themes']) . DS);
define('DIR_PUBLIC', DIR_ROOT . 'public' . DS);

if (APP === 'app') {
	define('DIR_THEME', DIR_ROOT . implode(DS, ['public', 'themes']) . DS);
} else {
	define('DIR_THEME', DIR_ROOT . 'public' . DS . APP . DS);
}

define('DIR_APP', DIR_ROOT . APP . DS);
define('DIR_TEMPLATE', DIR_APP . 'template' . DS);
define('DIR_MEDIA', DIR_PUBLIC . 'media' . DS);
define('CDATA_START', '<![CDATA[');
define('CDATA_END', ']]>');

$error_log = ini_get('error_log');

if (!$error_log || $error_log === '/dev/null') {
	ini_set('error_log', $storage_dir . 'logs/error_log');
}

include DIR_SYSTEM . 'session.php';

include DIR_SYSTEM . 'component/component.php';

require_once DIR_SYSTEM . 'core/frontcontroller.php';

require_once DIR_SYSTEM . 'core/view.php';

require_once DIR_SYSTEM . 'functions.php';

require_once DIR_SYSTEM . 'event.php';

/**
 * Log an error message to the error log.
 *
 * @param string $message The error message to log.
 *
 * @return bool True if the message was successfully written to the log.
 */
function logError(string $message): bool {
	return error_log($message);
}

/**
 * Regenerate a SQL model file from a SQL definition file.
 *
 * @param string $sqlFile   Path to the SQL definition file.
 * @param string $file      Output path for the generated PHP model file.
 * @param string $modelName The model class name.
 * @param string $namespace The namespace for the generated model class.
 *
 * @return void
 */
function regenerateSQL(string $sqlFile, string $file, string $modelName, string $namespace): void {
	$sqlp = new Sqlp();

	$sqlp->parseSqlPfile($sqlFile, $modelName, $namespace);

	$dir = dirname($file);

	if (!file_exists($dir)) {
		@mkdir($dir, 0755, true);
	}

	$code = "<?php \n" . $sqlp->generateModel();

	//never write a broken model to disk, a parse error here would poison every request
	try {
		$tokens = token_get_all($code, TOKEN_PARSE);
		unset($tokens);
	} catch (\ParseError $e) {
		throw new \Exception(sprintf('Generated SQL model %s has invalid syntax!', $file));
	}

	//write to a temp file and rename, so concurrent requests never read a half written model
	$tmp = $file . '.' . getmypid() . '.tmp';
	file_put_contents($tmp, $code);
	rename($tmp, $file);
}

/**
 * Autoload classes under the Vvveb namespace.
 *
 * Resolves class names to file paths, handling plugin SQL classes,
 * system classes, app/admin controllers, and standard models.
 *
 * @param string $class The fully-qualified class name to autoload.
 *
 * @return void
 */
function autoload(string $class): void {
	// project-specific namespace prefix
	$prefix = 'Vvveb\\';

	// does the class use the namespace prefix?
	$len = strlen($prefix);

	if (strncmp($prefix, $class, $len) !== 0) {
		// no, move to the next registered autoloader
		return;
	}

	$relativeClass = substr($class, $len);
	$isPlugin      = str_starts_with($relativeClass, 'Plugins\\');
	// replace the namespace prefix with the base directory, replace namespace
	// separators with directory separators in the relative class name, append
	// with .inc
	$root = DIR_APP;

	//if namespace is Vvveb\System or Vvveb\Plugins load from root dir above app dir
	if ((str_starts_with($relativeClass, 'System\\')) ||
		$isPlugin ||
		(str_starts_with($relativeClass, 'Sql\Plugins\\'))) {
		$root = DIR_ROOT;
	}

	//if namespace is App change to app dir
	if (str_starts_with($relativeClass, 'App\\')) {
		$root = DIR_ROOT . 'app' . DS;
	}

	//if namespace is Admin change to admin dir
	if (str_starts_with($relativeClass, 'Admin\\')) {
		$root = DIR_ROOT . 'admin' . DS;
	}

	//sql model classes are generated from .sql definition files
	if (substr_compare($relativeClass, 'SQL', -3, 3) === 0) {
		autoloadSqlModel($relativeClass);

		return;
	}

	//load any other Vvveb class from the app/system/plugin directories
	$file     = $root . str_replace('\\', '/', camelToUnderscore($relativeClass)) . '.php';
	$fileAsIs = $root . str_replace('\\', '/', strtolower($relativeClass)) . '.php';

	if ($isPlugin && ($isController = str_contains($relativeClass, 'Controller\\'))) {
		$file     = str_replace('/controller/', '/' . APP . '/controller/', $file);
		$fileAsIs = str_replace('/controller/', '/' . APP . '/controller/', $fileAsIs);
	}

	foreach ([$file, $fileAsIs] as $f) {
		if (file_exists($f)) {
			require_once $f;

			return;
		}
	}

	//throw(new \Exception($class . ' could not be loaded!'));
}

/**
 * Resolve, generate when stale, and load a SQL model class.
 *
 * Called by autoload() for every class ending in SQL.
 *
 * @param string $relativeClass Class name without the Vvveb\ prefix.
 */
function autoloadSqlModel(string $relativeClass): void {
	$isFromPlugin = str_contains($relativeClass, 'Plugins\\');
	$sqlFile      = str_replace(['Sql', '\\'], ['', DS], substr($relativeClass, 0, -3));

	if ($isFromPlugin) {
		//convert camelCase to snake_case
		$sqlFile    = strtolower(preg_replace('/(?<!^)(?<!\/)(?<!\\\)[A-Z]/', '-$0', $sqlFile));
		$file       = basename($sqlFile);
		$pluginName = str_replace([DS . 'plugins' . DS, DS . $file], '', $sqlFile) . DS;
		$pluginName = strtolower(preg_replace('/(?<!^)(?<!\/)(?<!\\\)[A-Z]/', '-$0', $pluginName));
		$sqlFile    = DIR_PLUGINS . $pluginName . 'sql' . DS . DB_ENGINE . DS . $file . '.sql';
	} else {
		$sqlFile   = strtolower($sqlFile) . '.sql';
	}

	$name      = str_replace(['\\', 'sql' . DS], [DS, ''], strtolower($relativeClass));
	$modelName = ucwords(basename(str_replace('sql', '', $name)));
	$namespace = ucwords(dirname($name));

	if ($namespace !== '.') {
		$namespace = str_replace('/', '\\', "\\$namespace");
	} else {
		$namespace = '';
	}

	$file       = DIR_STORAGE . 'model' . DS . APP . DS . $name . '.' . DB_ENGINE . '.php';
	$fileExists = file_exists($file);

	if (SQL_CHECK || !$fileExists) {
		$sqlExists = false;

		if ($isFromPlugin) {
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

		if (!$sqlExists) {
			throw new \Exception(sprintf(\Vvveb\__('SQL file %s does not exist for %s!'), $sqlFile, $relativeClass));
		}
		//if the file has not been generated yet or sql files is changed recompile
		if (!$fileExists || ((filemtime($fullSqlFile) > filemtime($file)))) {
			regenerateSQL($fullSqlFile, $file, $modelName, $namespace);
			$fileExists = true;
		}
	}

	require_once $file;
}

/**
 * Autoload vendor classes from the vendor directory.
 *
 * @param string $class The fully-qualified class name to autoload.
 *
 * @return void
 */
function autoload_vendor(string $class): void {
	$path = str_replace('\\', DS, $class);

	$file = DIR_ROOT . 'vendor' . DS . "$path.php";

	// if the file exists, require it
	if (file_exists($file)) {
		require_once $file;
	}
}

/**
 * Convert a throwable exception to a structured array with context.
 *
 * Extracts the message, file, line, surrounding code context, and stack trace
 * from the given exception.
 *
 * @param \Throwable   $exception The exception to convert.
 * @param string|false $file      Optional override for the source file path.
 *
 * @return array<string, mixed> Structured error information array<array-key, mixed>.
 */
function exceptionToArray(\Throwable $exception, string|false $file = false): array {
	$file   = $exception->getFile() ?: $file;
	$lineNo = $exception->getLine() - 1;
	//$code = $exception->getCode();
	$class     = $exception::class;
	$lines     = [];
	$codeLines = [];
	$line      = $lineNo;
	$code      = '';

	if (!defined('CLI') && $file && file_exists($file) && ($codeLines = file($file)) && isset($codeLines[$lineNo])) {
		$codeLines[$lineNo] = preg_replace("/\n$/", "\t // <==\n", $codeLines[$lineNo]);
		$lines              = array_slice($codeLines, $lineNo - 7, 14);
		$line               = implode("\n", array_slice($codeLines, $lineNo, 1));
		$before             = implode("\n", array_slice($codeLines, $lineNo - 6, 5));
		$after              = implode("\n", array_slice($codeLines, $lineNo + 1, 5));
		$code               = "$before<b>$line</b>$after";
	}

	return [
		'message' => $exception->getMessage(),
		'file'    => $file,
		'line_no' => $lineNo,
		'line'    => $line,
		'lines'   => $lines,
		'trace'   => $exception->getTraceAsString(),
		'code'    => $codeLines,
	];
}

/**
 * Handle uncaught exceptions globally.
 *
 * Converts the exception to an array, checks for plugin errors, and
 * outputs debug information when DEBUG mode is enabled.
 *
 * @param \Throwable $exception The uncaught exception.
 *
 * @return void
 */
function exceptionHandler(\Throwable $exception): void {
	static $handled;

	if ($handled) {
		if (DEBUG) {
			echo $exception->getMessage() . ' in ' . $exception->getFile() . ' on line ' . $exception->getLine();
		} else {
			exit('Server error, enable debug for more info');
		}

		return;
	}
	$handled = true;

	$message = exceptionToArray($exception, $exception->getFile());
	//check if error is generated by a plugin and disable it
	pluginErrorCheck($message['file']);

	//FrontController::notFound(false, $message, 500);
	//pluginErrorCheck($file);
	if (DEBUG) {
		echo '<b>Exception:</b><pre>';
		print_r($message);
		echo '</pre>';
	}
}

/**
 * Custom error handler for PHP errors.
 *
 * Checks if the error is within the current error_reporting level, handles
 * plugin errors, and throws ErrorException for fatal errors.
 *
 * @param int    $errno   The error level constant (e.g. E_WARNING).
 * @param string $errstr  The error message.
 * @param string $errfile The filename where the error occurred.
 * @param int    $errline The line number where the error occurred.
 *
 * @return bool Returns false to allow the PHP error handler to continue.
 */
function vErrorHandler(int $errno, string $errstr, string $errfile, int $errline): bool {
	if (!(error_reporting() && $errno)) {
		// This error code is not included in error_reporting, so let it fall
		// through to the standard PHP error handler
		return false;
	}
	// $errstr may need to be escaped:
	$errstr = htmlspecialchars($errstr);

	switch ($errno) {
		case E_USER_WARNING:
		case E_WARNING:
		case E_USER_NOTICE:
			break;

		case E_ERROR:
		case E_USER_ERROR:
			//check if error is generated by a plugin and disable it
			pluginErrorCheck($errfile);

			throw new \ErrorException($errstr, $errno, 0, $errfile, $errline);
			//return FrontController::notFound(false, $errstr, 500);
	}

	// Don't execute PHP internal error handler
	return false;
}

/**
 * Check if an error originates from a plugin and optionally disable it.
 *
 * If the error file path is within the plugins directory, logs the error
 * and deactivates the offending plugin when DISABLE_PLUGIN_ON_ERORR is true.
 *
 * @param string $file The file path where the error occurred.
 *
 * @return bool True if the error is from a plugin, false otherwise.
 */
function pluginErrorCheck(string $file): bool {
	if (str_contains($file, DIR_PLUGINS)) {
		$plugin = \Vvveb\filter('@([^/]+)@', str_replace(DIR_PLUGINS, '', $file));
		logError("'$plugin' plugin triggers fatal error.");

		if (DISABLE_PLUGIN_ON_ERORR) {
			logError(sprintf(\Vvveb\__('Disabling "%s" plugin.'), $plugin));
			Plugins::deactivate($plugin);
		}

		return true;
	}

	return false;
}

/**
 * Shutdown handler that catches fatal errors.
 *
 * Retrieves the last error and delegates it to the custom error handler.
 *
 * @return void
 */
function fatalErrorHandler(): void {
	$message = error_get_last();

	if ($message) {
		vErrorHandler($message['type'], $message['message'], $message['file'], $message['line']);
	}
}

spl_autoload_register('Vvveb\System\Core\autoload');
//spl_autoload_register('Vvveb\System\Core\autoload_vendor');
set_exception_handler('Vvveb\System\Core\exceptionHandler');
set_error_handler('Vvveb\System\Core\vErrorHandler');
register_shutdown_function('Vvveb\System\Core\fatalErrorHandler');

require DIR_ROOT . '/vendor/autoload.php';

$dbDefault  = \Vvveb\config('db.default', 'default');
$connection = \Vvveb\config('db.connections.' . $dbDefault, []);

if ($connection) {
	// Define default database configuration
	// skipped when an entry point (e.g. the test bootstrap) already defined
	// DB_ENGINE to run against a different backend than config/db.php
	if (!defined('DB_ENGINE')) {
		define('DB_ENGINE', $connection['engine']);
		define('DB_HOST', $connection['host'] ?? '');
		define('DB_USER', $connection['user'] ?? '');
		define('DB_PASS', $connection['password'] ?? '');
		define('DB_NAME', $connection['database'] ?? '');
		define('DB_PREFIX', $connection['prefix'] ?? '');
		define('DB_PORT', $connection['port'] ?? null);
	}
}

if (defined('DB_ENGINE')) {
	define('DIR_SQL', DIR_APP . 'sql' . DS . DB_ENGINE . DS);
}

/**
 * Bootstrap the application by resolving the site and dispatching the request.
 *
 * Loads the site configuration, defines site constants, loads plugins for
 * the frontend, and dispatches the request via the FrontController.
 *
 * @return void
 */
function start(): void {
	$site_id = false;
	$path    = $_SERVER['REQUEST_URI'] ?? '/';

	if (isset($_GET['site_id'])) {
		//check if admin user to allow site id override for editor
		if ($admin = \Vvveb\System\User\Admin :: current()) {
			$site_id = (int)$_GET['site_id'];
		}
	}

	$site = Sites :: getSiteData($site_id, $path);

	if ($site) {
		define('SITE_URL', $site['host']);
		define('SITE_ID', $site['site_id'] ?? 1);
		define('SITE_URI', $site['uri'] ?? '');
		define('SITE_PATH', $site['path'] ?? '');

		//load plugins first for APP
		if (APP !== 'admin') {
			Plugins :: loadPlugins(SITE_ID);
		}

		defined('DISPATCH') || define('DISPATCH', true);

		if (DISPATCH) {
			FrontController::dispatch($site);
		}
	} else {
		define('SITE_URL', $_SERVER['HTTP_HOST'] ?? 'localhost');
		define('SITE_ID', 1);
		define('SITE_URI', '');
		define('SITE_PATH', '');
		FrontController::notFound(false, 'Website not found!');
	}
}
