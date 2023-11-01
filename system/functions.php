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

function url($parameters, $mergeParameters = false, $useCurrentUrl = true) {
	if (is_string($parameters) && $parameters) {
		$result = '';

		if (isset($mergeParameters['scheme'])) {
			$result .= $mergeParameters['scheme'] . ':';
			unset($mergeParameters['scheme']);
		}

		if (isset($mergeParameters['host'])) {
			$result .= '//' . \Vvveb\System\Sites::url($mergeParameters['host']);
			unset($mergeParameters['host']);
		}

		$url = System\Routes::url($parameters, $mergeParameters);
		$result .= $url ?? '';
	} else {
		static $url       = '';
		static $urlParams = [];

		if ($useCurrentUrl) {
			$url=parse_url($_SERVER['REQUEST_URI'] ?? '');

			if (isset($url['query'])) {
				parse_str($url['query'], $urlParams);
			}
		}

		if (! is_array($parameters)) {
			$parameters = [];
		}

		if ($mergeParameters) {
			if (is_array($mergeParameters)) {
				$mergeParameters = array_merge($urlParams, $mergeParameters);
			} else {
				$mergeParameters = $urlParams;
			}

			if (is_array($mergeParameters)) {
				$parameters = array_merge($mergeParameters, $parameters);
			}
		}

		$result = '';

		if (isset($parameters['host'])) {
			$result .= '//' . \Vvveb\System\Sites::url($parameters['host']);
			unset($parameters['host']);
		}

		$result .= ($useCurrentUrl ? $url['path'] ?? '' : '') . ($parameters ? '?' . urldecode(http_build_query($parameters)) : '');
	}

	return $result;
}

function config($key = null, $default = null) {
	if (is_null($key)) {
		return System\Config::getInstance();
	}

	return System\Config::getInstance()->get($key, $default);
}

function get_config($key = null, $default = null) {
	return System\Config::getInstance()->get($key, $default);
}

function set_config($key, $value = null) {
	return System\Config::getInstance()->set($key, $value);
}

function unset_config($key) {
	return System\Config::getInstance()->unset($key);
}

function get_setting($namespace, $key = null, $default = null, $site_id = SITE_ID) {
	return System\Setting::getInstance()->get($namespace, $key, $default, $site_id);
}

function set_setting($namespace, $key = null, $value = null, $site_id = SITE_ID) {
	return System\Setting::getInstance()->set($namespace, $key, $value, $site_id);
}

function delete_setting($namespace, $key = null, $site_id = SITE_ID) {
	return System\Setting::getInstance()->delete($namespace, $key, $value, $site_id);
}

function set_settings($namespace, $settings, $site_id = SITE_ID) {
	return System\Setting::getInstance()->multiSet($namespace, $settings, $site_id);
}

function getCurrentTemplate() {
	return System\Core\View :: getInstance()->template();
}

function getUrlTemplate($url) {
	$urlData = \Vvveb\System\Routes::getUrlData($url);

	return $urlData;
}

function getCurrentUrl() {
	return $_SERVER['REQUEST_URI'] ?? '';
}

function adminPath() {
	return (V_SUBDIR_INSTALL ? V_SUBDIR_INSTALL : '') . '/' . \Vvveb\config('admin.path', 'admin') . '/';
}

function publicUrlPath() {
	$public_path = PUBLIC_PATH;

	if (V_SUBDIR_INSTALL) {
		$public_path = str_replace(V_SUBDIR_INSTALL, '', $public_path);
	}

	if ($public_path == '/public/' || $public_path == '/public/admin/') {
		return PUBLIC_PATH;
	} else {
		return '/';
	}
}

function themeUrlPath() {
	$theme = \Vvveb\System\Core\View::getInstance()->getTheme();
	//$theme = 'landing';
	if (APP == 'app') {
		$path = PUBLIC_THEME_PATH . 'themes/' . $theme . '/';
	} else {
		$path = PUBLIC_THEME_PATH . $theme . '/';
	}

	return $path;
}

function escUrl($url) {
	return htmlspecialchars($url, ENT_QUOTES, 'UTF-8');
}

function escAttr($attr) {
	return htmlspecialchars($attr);
}

function escHtml($url) {
	return htmlspecialchars($url);
}

function env($key, $default = null) {
	if ($env = getenv($key)) {
		return $env;
	} else {
		return $default;
	}
}

if (! function_exists('nggetext')) {
	function nggetext($singular, $plural, $number) {
		return ($number > 1) ? $plural : $singular;
	}
}

function friendlyDate($date) {
	$fileformats = [
		1          => function ($i) { return __('%d second', '%d seconds', $i); },
		60         => function ($i) { return __('%d minute', '%d minutes', $i); },
		3600       => function ($i) { return __('%d hour', '%d hours', $i); },
		86400      => function ($i) { return __('%d day', '%d days', $i); },
		604800     => function ($i) { return __('%d week', '%d weeks', $i); },
		2592000    => function ($i) { return __('%d month', '%d months', $i); },
		31536000   => function ($i) { return __('%d year', '%d years', $i); },
		315360000  => function ($i) { return __('%d decade', '%d decades', $i); },
		3153600000 => function ($i) { return __('%d century', '%d centuries', $i); },
	];

	$time_direction = __(' ago');
	$diff           = time() - strtotime($date) + 10;

	if ($diff < 0) {
		$time_direction = __(' from now');
		$diff           = abs($diff);
	}

	$lastTime = 1;
	$lastText = $fileformats[1]($diff);

	foreach ($fileformats as $time => $text) {
		if ($diff < $time) {
			$units = floor($diff / $lastTime);

			return sprintf($lastText($units), $units) . $time_direction;
		}
		$lastText = $text;
		$lastTime = $time;
	}

	return $date;
}

/*
Convert var.key1.key2 > var['key1']['key2']
*/
function dotToArrayKey($key) {
	//var.key1.key2 > var['key1']['key2']
	//var.key1 > var['key1']

	return preg_replace_callback('/\.([-_\w]+)/', function ($matches) {
		return "['" . str_replace("'", "\'", $matches[1]) . "']";
	}, $key);
}

/*
Convert name[key] > ['name']['key']
*/
function postNameToArrayKey($key) {
	$key = str_replace(['][', '[', ']'] , ['.', '.', ''], $key);

	return '[\'' . str_replace('.', '\'][\'', $key) . '\']';
}

/*
Prefix array keys 
*/
function prefixArrayKeys($prefix, &$array) {
	if (is_array($array)) {
		foreach ($array as $k => $v) {
			$array["$prefix$k"] = $v;
			unset($array[$k]);
		}
	}

	return $array;
}

function filterText($data) {
	return urldecode(filter_var($data, FILTER_SANITIZE_FULL_SPECIAL_CHARS, FILTER_FLAG_ENCODE_HIGH | FILTER_FLAG_ENCODE_LOW));
}

function session($data, $default = null) {
	$session = System\Session :: getInstance();

	if (is_array($data)) {
		foreach ($data as $key => $value) {
			$session->set($key, $value);
		}
	} else {
		$value = $session->get($data);

		if ($default && ! $value) {
			return $default;
		}

		return $value;
	}
}

/*
 * Regex filter to allow only matched content
 *
 * @param string $regex 
 * @param string $input 
 * @param integer $maxInputSize [100] 
 */
function filter($regex, $input, $maxInputSize = 100) {
	$matches = [];

	if (preg_match($regex, substr($input, 0, $maxInputSize), $matches)) {
		return $matches[0];
	} else {
		return false;
	}
}

/**
 * Shortcut for preg_match to return the matched parameter directly.
 * 
 * @param string $regex 
 * @param string $input 
 * @param integer $level [false] 
 *
 * @return mixed 
 */
function pregMatch($regex, $input, $level = false) {
	$matches = [];

	//if (preg_match($regex, $input, $matches, PREG_UNMATCHED_AS_NULL | PREG_PATTERN_ORDER)) {
	if (preg_match($regex, $input, $matches, PREG_UNMATCHED_AS_NULL)) {
		if ($level !== false) {
			return $matches[$level];
		}

		return $matches;
	} else {
		return false;
	}
}

/**
 * Shortcut for preg_match_all to return the matched parameters directly.
 * 
 * @param string $regex 
 * @param string $input 
 * @param integer $level [false] 
 *
 * @return mixed 
 */
function pregMatchAll($regex, $input, $level = false) {
	$matches = [];

	if (preg_match_all($regex, $input, $matches, PREG_UNMATCHED_AS_NULL | PREG_PATTERN_ORDER)) {
		if ($level !== false) {
			return $matches[$level];
		}

		return $matches;
	} else {
		return false;
	}
}

function arrayAllowValues($input, $allowedValues) {
	if (! in_array($input, $allowedValues)) {
		return null;
	} else {
		return $input;
	}
}

/*
 * Get values from multidimensional arrays based on path
 * For example for array ['item' => ['desc' => ['name' => 'test']]] the path "item.description.name" will return "test".
 * 
 * */

function arrayPath(array $a, $path, $default = null, $token = '.') {
	$p = strtok($path, $token);

	while ($p !== false) {
		if (! isset($a[$p])) {
			return $default;
		}

		$a = $a[$p];
		$p = strtok($token);
	}

	return $a;
}

function humanReadable($text) {
	return ucfirst(str_replace(['_', '-', '/', '[', ']', '.'], [' ', ' ', ' - ', ' ', ' ', ' '], trim($text, ' /\-_')));
}

function cleanUrl($text, $divider = '-') {
	// replace non letter or digits by divider
	$text = preg_replace('/[^\pL\d]+/u', $divider, $text);
	
	// remove unwanted characters
	$text = preg_replace('/[^\\-\w]+/', '', $text);

	$text = trim($text, $divider);

	return $text;
}

function slugify($text, $divider = '-') {
	// replace non letter or digits by divider
	$text = preg_replace('/[^\pL\d]+/u', $divider, $text);

	// transliterate
	if (function_exists('iconv')) {
		$text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
	}

	// remove unwanted characters
	$text = preg_replace('/[^-\w]+/', '', $text);

	// trim
	$text = trim($text, $divider);

	// remove duplicate divider
	$text = preg_replace('/-+/', $divider, $text);

	// lowercase
	$text = strtolower($text);

	return $text;
}

if (function_exists('_')) {
	function __($text, $plural = false, $count = false) {
		if ($plural) {
			return ngettext($text, $plural, $count);
		} else {
			return gettext($text);
		}
	}
} else {
	function __($text, $plural = false, $count = false) {
		return $text;
	}
}

function isAdmin() {
	return System\User\Admin::current() ? true : false;
}

function cssToXpath($selector) {
	//if already xpath don't transform
	//if (substr_compare($selector,'vtpl_xpath', 0, 11) == 0) return substr($selector, 12, -1);

	$selector = (string) $selector;

	//convert , to | union operator to allow multiple queries
	$selector = str_replace(',', '|', $selector);

	$cssSelector = [
		// E > F: Matches any F element that is a child of an element E
		'/\s*>\s*/',

		// E + F: Matches any F element immediately preceded by an element
		'/\s*\+\s*/',

		// E F: Matches any F element that is a descendant of an E element
		'/([a-zA-Z\*="\[\]#._-])\s+([a-zA-Z\*="\[\]#._-])/', //'/([a-zA-Z\*="\[\]#._-])\s+([a-zA-Z\*#._-])/',

		// E:first-child: Matches element E when E is the first child of its parent
		'/([a-z#\.]\w*):first-child/',

		// E:nth-child() Matches the nth child element
		'/([a-z#\.]\w*):nth-child\((\d+)\)/',

		// E:first: Matches the first element from the set
		'/([a-z#\.]\w*):first/',

		// E:nth(2): Matches the nth element from the set
		'/([a-z#\.]\w*):nth\((\d+)\)/',

		// E[foo="warning"]: Matches any E element whose "foo" attribute value is exactly equal to "warning"
		'/([a-z]\w*)\[([a-z][\w\-_]*)\="([^"]*)"]/',

		// E[foo]: Matches any E element with the "foo" attribute set (whatever the value)
		'/([a-z]\w*)\[([a-z][\w_\-]*)\]/',

		// E[!foo]: Matches any E element without the "foo" attribute set
		'/([a-z]\w*)\[!([a-z][\w\-_]*)\]/',

		// [foo="warning"]: Matches any element whose "foo" attribute value is exactly equal to "warning"
		'/\[([a-z][\w\-_]*)\=\"(.*)\"\]/',

		// [foo*="warning"]: Matches any element whose "foo" attribute value contains the string "warning" and has other attributes
		'/(?<=\])\[([a-z][\w\-_]*)\*\=\"([^"]+)\"\]/',

		// [foo*="warning"]: Matches any element whose "foo" attribute value contains the string "warning"
		'/\[([a-z][\w\-_]*)\*\=\"([^"]+)\"\]/',

		// [foo^="warning"]: Matches any element whose "foo" attribute value begins with the string "warning"
		'/\[([a-z][\w_\-]*)\^\=\"([^"]+)\"\]/',

		// [foo$="warning"]: Matches any element whose "foo" attribute value ends  with the string "warning"
		'/\[([a-z][\w_\-]*)\$\=\"([^"]+)\"\]/',

		// [foo][baz]: Matches any element with the "foo" attribute set (whatever the value)
		'/(?<=\])(?<! )\[([a-z][\w_\-]*)\]/',

		// [foo]: Matches any element with the "foo" attribute set (whatever the value)
		'/\[([a-z][\w_\-]*)\]/',

		// element[foo*]: Matches any element that starts with "foo" attribute (whatever the value)
		'/(\w+)\[([a-z][\w\-]*)\*\]/',

		// [foo*]: Matches any element that starts with "foo" attribute (whatever the value) and has other attributes
		'/(?<=\])\[([a-z][\w\-]*)\*\]/',

		// [foo*]: Matches any element that starts with "foo" attribute (whatever the value) and is a single attribute
		'/(?<!\])\[([a-z][\w\-]*)\*\]/',

		// div.warn*: HTML only. The same as DIV[class*="warning"]
		'/([a-z]\w*|\*)\.([a-z][\w\-_]*)\*/',

		// div.warning: HTML only. The same as DIV[class~="warning"]
		'/([a-z]\w*|\*)\.([a-z][\w\-_]*)+/',

		// .warn*: HTML only. The same as [class*="warning"]
		'/\.([a-z][\w\-\_]*)\*/',

		// .warning: HTML only. The same as [class~="warning"]
		'/\.([a-z][\w\-\_]*)+/',

		// E#myid: Matches any E element with id-attribute equal to "myid"
		'/([a-z]\w*)\#([a-z][\w\-_]*)/',

		// #myid: Matches any E element with id-attribute equal to "myid"
		'/\#([a-z][\w\-_]*)/',
	];

	$xpathQuery = [
		'/', //element > child
		'/following-sibling::*[1]/self::', // element + precedent
		'\1//\2', //element descendent
		'*[1]/self::\1', //element:first-child
		'*[\2]/self::\1', //element:nth-child(2)
		'\1[1]', //element:first
		'\1[\2]', //element:nth(2)
		'\1[ contains( concat( " ", @\2, " " ), concat( " ", "\3", " " ) ) ]', //element[attribute="string"]
		'\1 [ @\2 ]', //element[attribute]
		'\1 [ not(@\2) ]', //element[!attribute]
		'[ contains( concat( " ", @\1, " " ), concat( " ", "\2", " " ) ) ]', //[foo="warning"]
		'*[ contains( concat( " ", @\1, " " ), concat( " ", "\2", " " ) ) ]', //[foo="warning"]
		'*[ contains( concat( " ", @\1, " " ), "\2" ) ]', //[foo*="warning"]
		'*[ contains( concat( " ", @\1, " " ), concat( " ", "\2", " " ) ) ]', //[foo^="warning"] not implemented
		'*[ contains( concat( " ", @\1, " " ), concat( " ", "\2", " " ) ) ]', //[foo$="warning"] not implemented
		'[ @\1 ]', //[attribute][attribute]
		'*[ @\1 ]', //[attribute]
		'\1 [ @*[starts-with(name(), "\2")] ]', //element[attr*]
		'[ @*[starts-with(name(), "\1")] ]', //[attr*] - attribute with other attributes
		'*[ @*[starts-with(name(), "\1")] ]', //[attr*] - single attribute
		'\1[ contains( concat( " ", @class, " " ), concat( " ", "\2") ) ]', //element[class*="string"]
		'\1[ contains( concat( " ", @class, " " ), concat( " ", "\2", " " ) ) ]', //element[class~="string"]
		'*[ contains( concat( " ", @class, " " ), concat( " ", "\1") ) ]', //[class*="string"]
		'*[ contains( concat( " ", @class, " " ), concat( " ", "\1", " " ) ) ]', //element[class~="string"]
		'\1[ @id = "\2" ]', //element#id
		'*[ @id = "\1" ]', //#id
	];

	$result = (string) '//' . preg_replace($cssSelector, $xpathQuery, $selector);

	return $result;
}

function dashesToCamelCase($string, $dash = '-') {
	return str_replace($dash, '', ucwords($string, $dash));
}

/**
 * Remove extra spaces.
 * @param mixed $string 
 *
 * @return string 
 */
function stripExtraSpaces($string) {
	foreach (['\t', '\n', '\r', ' '] as $space) {
		$string = preg_replace('/(' . $space . ')' . $space . '+/', '\1', $string);
	}

	return $string;
}

function tail($filename, $lines = 1000) {
	$file = @fopen($filename, 'rb');

	if ($file === false) {
		return false;
	}

	$buffer = 4096;
	$output = '';
	$chunk  = '';

	@fseek($file, -1, SEEK_END);

	if (fread($file, 1) != "\n") {
		$lines -= 1;
	}

	while (ftell($file) > 0 && $lines >= 0) {
		$seek = min(ftell($file), $buffer);
		fseek($file, -$seek, SEEK_CUR);
		$output = ($chunk = fread($file, $seek)) . $output;
		//fseek($file, -mb_strlen($chunk, '8bit'), SEEK_CUR);
		fseek($file, strlen($chunk), SEEK_CUR);
		$lines -= substr_count($chunk, "\n");
	}

	while ($lines++ < 0) {
		$output = substr($output, strpos($output, "\n") + 1);
	}

	fclose($file);

	return trim($output);
}

/**
 * Return current module name.
 *
 * @return string 
 */
function getModuleName() {
	return strtolower(\Vvveb\System\Core\FrontController::getModuleName());
}

/**
 * Return current action name.
 *
 * @return string 
 */
function getActionName() {
	return strtolower(\Vvveb\System\Core\FrontController::getActionName());
}

/*
 * Inserts a new key/value before the key in the array.
 *
 * @param $key
 *   The key to insert before.
 * @param $array
 *   An array to insert in to.
 * @param $new_key
 *   The key to insert.
 * @param $new_value
 *   An value to insert.
 *
 * @return
 *   The new array if the key exists, otherwise the unchanged array.
 *
 * @see array_insert_after()
 */
function array_insert_before($key, array &$array, $new_key, $new_value) {
	if (array_key_exists($key, $array)) {
		$new = [];

		foreach ($array as $k => $value) {
			if ($k === $key) {
				$new[$new_key] = $new_value;
			}
			$new[$k] = $value;
		}

		return $new;
	}

	return $array;
}

/*
 * Inserts a new array before the key in the array.
 *
 * @param $key
 *   The key to insert before.
 * @param $array
 *   An array to insert in to.
 * @param $new_key
 *   The key to insert.
 * @param $new_value
 *   An value to insert.
 *
 * @return
 *   The new array if the key exists, otherwise the unchanged array.
 *
 * @see array_insert_after()
 */
function array_insert_array_before($key, array &$array, $new_array) {
	if (array_key_exists($key, $array)) {
		$new = [];

		foreach ($array as $k => $value) {
			if ($k === $key) {
				$new += $new_array;
			}
			$new[$k] = $value;
		}

		return $new;
	}

	return $array;
}

/*
 * Inserts a new key/value after the key in the array.
 *
 * @param $key
 *   The key to insert after.
 * @param $array
 *   An array to insert in to.
 * @param $new_key
 *   The key to insert.
 * @param $new_value
 *   An value to insert.
 *
 * @return
 *   The new array if the key exists, otherwise the unchanged array.
 *
 * @see array_insert_before()
 */
function array_insert_after($key, array &$array, $new_key, $new_value) {
	if (array_key_exists($key, $array)) {
		$new = [];

		foreach ($array as $k => $value) {
			$new[$k] = $value;

			if ($k === $key) {
				$new[$new_key] = $new_value;
			}
		}

		return $new;
	}

	return $array;
}

/*
 * Inserts a new array after the key in the array.
 *
 * @param $key
 *   The key to insert after.
 * @param $array
 *   An array to insert in to.
 * @param $new_key
 *   The key to insert.
 * @param $new_value
 *   An value to insert.
 *
 * @return
 *   The new array if the key exists, otherwise the unchanged array.
 *
 * @see array_insert_before()
 */
function array_insert_array_after($key, array &$array, $new_array) {
	if (array_key_exists($key, $array)) {
		$new = [];

		foreach ($array as $k => $value) {
			$new[$k] = $value;

			if ($k === $key) {
				$new += $new_array;
			}
		}

		return $new;
	}

	return $array;
}

//request
function get($key) {
	return System\Core\Request::getInstance()->get[$key] ?? false;
}

/**
 * Check if the page is loaded in the editor.
 *
 * @return boolean
 */
function isEditor() {
	return isset($_GET['r']);
}

function log_error($message) {
	error_log($message);
}

function getThemeFolderList($theme = false) {
	$skipFolders    = ['src', 'source', 'backup', 'sections', 'blocks', 'inputs', 'css', 'scss', 'fonts', 'img', 'import', 'node_modules', 'screenshots', 'video', 'js'];

	if (! $theme) {
		$theme = \Vvveb\System\Sites::getTheme() ?? 'default';
	}

	$themeFolder = DIR_THEMES . DS . $theme;
	$files       = glob("$themeFolder/*", GLOB_ONLYDIR);
	$pages['/']  = ['name' => '/', 'title' => '/', 'filename' => '/', 'file' => '/', 'path' => '/', 'folder' => $theme];

	foreach ($files as $file) {
		$folder     = preg_replace('@^.*/themes/[^/]+/@', '', $file);
		$filename   = basename($file);

		if (in_array($folder, $skipFolders)) {
			continue;
		}

		//$path = PUBLIC_PATH . "themes/$theme/$folder";

		$pages[$file]  = ['name' => $filename, 'title' => "/$filename", 'filename' => $filename, 'file' => $filename, 'folder' => $theme];
	}

	return $pages;
}

function getTemplateList($theme = null, $skip = []) {
	$friendlyNames =  [
		'index'                     => ['name' =>  __('Home page'), 'description'         =>  __('Website homepage'), 'global' => true],
		'index.coming-soon'         => ['name' =>  __('Coming soon'), 'description'       =>  __('Coming soon message page'), 'global' => true],
		'index.maintenance'         => ['name' =>  __('Under maintenance'), 'description' =>  __('Website under maintenance message page'), 'global' => true],
		'contact'                   => ['name' =>  __('Contact us page'), 'description'   =>  __('Contact us page')],
		'blank'                     => ['name' =>  __('Blank page'), 'description'        =>  __('Template page used for new pages')],
		'product'                   => ['name' =>  __('Product page'), 'description'      =>  __('Used to display a product'), 'editor' => ['template' => 'product'], 'global' => true],
		'error404'                  => ['name' =>  __('Page not found'), 'description'    =>  __('Shows when a page is not available'), 'global' => true],
		'error500'                  => ['name' =>  __('Server error'), 'description'      =>  __('Site error display page'), 'global' => true],
		'content-index'             => ['name' =>  __('Blog homepage'), 'description'     =>  __('Blog page with latest posts'), 'global' => true],
		'content-post'              => ['name' =>  __('Blog post'), 'description'         =>  __('Blog post'), 'editor' => ['template' => 'post'], 'global' => true],
		'product-index'             => ['name' =>  __('Shop page'), 'description'         =>  __('Shop homepage'), 'global' => true],
		'search-index'              => ['name' =>  __('Search page'), 'description'       =>  __('Search page'), 'global' => true],
		'user-index'                => ['name' =>  __('Dashboard'), 'description'         =>  __('User dashboard'), 'global' => true],
	];

	$pagesSortOrder = ['index' => '', 'contact' => '', 'blank' => '', 'error404' => '', 'error500' => ''];
	$skipFolders    = array_merge(['src', 'source', 'backup', 'sections', 'blocks', 'inputs', 'css', 'scss', 'screenshots', 'locale', 'node_modules'], $skip);

	if (! $theme) {
		$theme = \Vvveb\System\Sites::getTheme() ?? 'default';
	}
	$pages       = [];
	$themeFolder = DIR_THEMES . DS . $theme;
	$files       = glob("$themeFolder/{,*/*/,*/}*.html", GLOB_BRACE);

	foreach ($files as $file) {
		$file     = preg_replace('@^.*/themes/[^/]+/@', '', $file);
		$filename = basename($file);

		$folder   = \Vvveb\System\Functions\Str::match('@(\w+)/.*?$@', $file);
		$path     = \Vvveb\System\Functions\Str::match('@(\w+)/.*?$@', $file);

		if (in_array($folder, $skipFolders)) {
			continue;
		}
		$name        = $title       = str_replace('.html', '', $filename);
		$description = '';
		$name        = ! empty($folder) ? "$folder-$name" : $name;

		if (isset($friendlyNames[$name])) {
			if (isset($friendlyNames[$name]['description'])) {
				$description = $friendlyNames[$name]['description'];
			}

			$title = $friendlyNames[$name]['name'];
		}

		$url = PUBLIC_PATH . "themes/$theme/$file";

		$pages[$name]  = ['name' => $name, 'filename' => $filename, 'file' => $file, 'url' => $url, 'title' => humanReadable($title), 'folder' => $path, 'description' => $description];

		if (isset($friendlyNames[$name]['editor'])) {
			$pages[$name]['editor'] = $friendlyNames[$name]['editor'];
		}
	}

	//$pagesSortOrder = array_flip(array_keys($this->friendlyNames));
	$pages = array_filter(array_merge($pagesSortOrder, $pages));

	return $pages;
}

function sanitizeFileName($file) {
	//sanitize, remove double dot .. and remove get parameters if any
	$file = preg_replace('@\?.*$|\.{2,}|[^\/\\a-zA-Z0-9\-\._]@' , '', $file);
	$file = preg_replace('@[^\/\w\s\d\.\-_~,;:\[\]\(\)\\]|[\.]{2,}@', '', $file);
	//replace directory separators with OS specific separator
	$file = str_replace(['\\', '/'], DS, $file);

	return $file;
}

function formatBytes($bytes) {
	$i     = 0;
	$units = ['', 'K', 'M', 'G', 'T', 'P', 'E'];

	while (($remainder = ($bytes / 1024)) > 1) {
		$bytes = $remainder;
		$i++;
	}

	return round($bytes, 2) . ' ' . $units[$i] . 'B';
}

function model($model) {
	$modelClass = 'Vvveb\Sql\\' . ucwords($model) . 'SQL';

	return new $modelClass();
}

function d(...$variables) {
	foreach ($variables as $variable) {
		echo highlight_string("<?php\n" . var_export($variable, true), true);
	}
}

function dd(...$variables) {
	foreach ($variables as $variable) {
		echo highlight_string("<?php\n" . var_export($variable, true), true);
	}

	die();
}

function encrypt($key, $value, $cipher = 'aes-256-gcm', $digest = 'sha256') {
	$key       = openssl_digest($key, $digest, true);
	$iv_length = openssl_cipher_iv_length($cipher);
	$iv        = openssl_random_pseudo_bytes($iv_length);

	return base64_encode($iv . openssl_encrypt($value, $cipher, $key, OPENSSL_RAW_DATA, $iv));
}

function decrypt($key, $value, $cipher = 'aes-256-gcm', $digest = 'sha256') {
	$result    = false;

	$key       = openssl_digest($key, $digest, true);
	$iv_length = openssl_cipher_iv_length($cipher);
	$value     = base64_decode($value);
	$iv        = substr($value, 0, $iv_length);
	$value     = substr($value, $iv_length);

	if (strlen($iv) == $iv_length) {
		$result = openssl_decrypt($value, $cipher, $key, OPENSSL_RAW_DATA, $iv);
	}

	return $result;
}

function camelToUnderscore($string, $us = '-') {
	return strtolower(preg_replace(
	'/(?<=\d)(?=[A-Za-z])|(?<=[A-Za-z])(?=\d)|(?<=[a-z])(?=[A-Z])/', $us, $string));
}

function stripTags($string, $tags = ['script', 'iframe', 'applet']) {
	if ($string && $tags) {
		foreach ($tags as $tag) {
			$string = preg_replace("@<\s*$tag.*?>.*?</$tag\s*>@im", '', $string);
		}
	}

	return $string;
}

function stripTagsArray($array, $tags) {
	if (is_array($array) && $tags) {
		array_map($array, function ($key, $value) {
			return stripTags($value);
		});
	}

	return $array;
}

function sanitizeHTML($string) {
	if (! is_string($string)) {
		return $string;
	}

	//$string = stripTags($string);

	// Fix &entity\n;
	$string = str_replace(['&amp;', '&lt;', '&gt;'], ['&amp;amp;', '&amp;lt;', '&amp;gt;'], $string);
	$string = preg_replace('/(&#*\w+)[\x00-\x20]+;/u', '$1;', $string);
	$string = preg_replace('/(&#x*[0-9A-F]+);*/iu', '$1;', $string);
	$string = html_entity_decode($string, ENT_COMPAT, 'UTF-8');

	// Remove any attribute starting with "on" or xmlns
	$string = preg_replace('#(<[^>]+?[\x00-\x20"\'])(?:on|xmlns)[^>]*+>#iu', '$1>', $string);

	// Remove javascript: and vbscript: protocols
	$string = preg_replace('#([a-z]*)[\x00-\x20]*=[\x00-\x20]*([`\'"]*)[\x00-\x20]*j[\x00-\x20]*a[\x00-\x20]*v[\x00-\x20]*a[\x00-\x20]*s[\x00-\x20]*c[\x00-\x20]*r[\x00-\x20]*i[\x00-\x20]*p[\x00-\x20]*t[\x00-\x20]*:#iu', '$1=$2nojavascript...', $string);
	$string = preg_replace('#([a-z]*)[\x00-\x20]*=([\'"]*)[\x00-\x20]*v[\x00-\x20]*b[\x00-\x20]*s[\x00-\x20]*c[\x00-\x20]*r[\x00-\x20]*i[\x00-\x20]*p[\x00-\x20]*t[\x00-\x20]*:#iu', '$1=$2novbscript...', $string);
	$string = preg_replace('#([a-z]*)[\x00-\x20]*=([\'"]*)[\x00-\x20]*-moz-binding[\x00-\x20]*:#u', '$1=$2nomozbinding...', $string);

	// Only works in IE: <span style="width: expression(alert('Ping!'));"></span>
	$string = preg_replace('#(<[^>]+?)style[\x00-\x20]*=[\x00-\x20]*[`\'"]*.*?expression[\x00-\x20]*\([^>]*+>#i', '$1>', $string);
	$string = preg_replace('#(<[^>]+?)style[\x00-\x20]*=[\x00-\x20]*[`\'"]*.*?behaviour[\x00-\x20]*\([^>]*+>#i', '$1>', $string);
	$string = preg_replace('#(<[^>]+?)style[\x00-\x20]*=[\x00-\x20]*[`\'"]*.*?s[\x00-\x20]*c[\x00-\x20]*r[\x00-\x20]*i[\x00-\x20]*p[\x00-\x20]*t[\x00-\x20]*:*[^>]*+>#iu', '$1>', $string);

	// Remove namespaced elements (we do not need them)
	$string = preg_replace('#</*\w+:\w[^>]*+>#i', '', $string);

	do {
		// Remove really unwanted tags
		$old_data = $string;
		$string   = preg_replace('#</*(?:applet|b(?:ase|gsound|link)|embed|frame(?:set)?|i(?:frame|layer)|l(?:ayer|ink)|meta|object|s(?:cript|tyle)|title|xml)[^>]*+>#i', '', $string);
	} while ($string !== $string);

	return $string;
}

function availableLanguages() {
	$cache     = System\Cache::getInstance();
	$languages = $cache->cache(APP,'languages',function () {
		$languages             = new Sql\LanguageSQL();
		$result = $languages->getAll(['status' => 1]);

		if ($result && isset($result['language'])) {
			return $result['language'];
		}

		return [];
	}, 259200);

	return $languages;
}

function availableCurrencies() {
	$cache     = System\Cache::getInstance();
	$languages = $cache->cache(APP,'currency',function () {
		$currency             = new Sql\CurrencySQL();
		$result = $currency->getAll(['status' => 1]);

		if ($result && isset($result['currency'])) {
			return $result['currency'];
		}

		return [];
	}, 259200);

	return $languages;
}

function installedLanguages() {
	$languages = glob(DIR_ROOT . 'locale/*', GLOB_ONLYDIR);

	foreach ($languages as &$language) {
		$language = basename($language);
	}

	return $languages;
}

function userPreferedLanguages() {
	if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
		$languages = explode(',', $_SERVER['HTTP_ACCEPT_LANGUAGE']);

		foreach ($languages as &$language) {
			$language = \Vvveb\filter('/[-\w]+/', $language);
			$language = str_replace('-', '_', $language);
		}
	}

	return $languages ?? [];
}

function userPreferedLanguage() {
	$languages = userPreferedLanguages();
	$installed = installedLanguages();

	foreach ($languages as $language) {
		if (isset($installed[$language])) {
			return $language;
		}

		foreach ($installed as $lang) {
			if (strpos($lang, $language) === 0) {
				//return $language;
				return $lang;
			}
		}
	}

	return false;
}

/**
 * Change locale language for gettext.
 * 
 * @param string $langCode ['en_US'] 
 * @param string $domain ['vvveb'] 
 *
 * @return mixed 
 */
function setLanguage($langCode = 'en_US', $domain = 'vvveb') {
	//setlocale(LC_TIME, "");
	//\putenv('LOCPATH=' . DIR_ROOT. "locale");

	//translating theme text will change theme texts and break translation
	if (isEditor()) {
		return;
	}

	if (function_exists('bindtextdomain')) {
		bindtextdomain($domain, DIR_ROOT . 'locale');
		textdomain($domain);
		bind_textdomain_codeset($domain, 'utf8');

		setlocale(LC_ALL,'C.UTF-8');

		if (function_exists('putenv')) {
			@\putenv("LC_ALL=$langCode");
			@\putenv("LC_MESSAGES=$langCode");
			@\putenv("LANG=$langCode");
			@\putenv('LANGUAGE=' . $langCode);
		}

		if (defined('LC_MESSAGES')) {
			setlocale(LC_MESSAGES, "$langCode.utf8");
			setlocale(LC_CTYPE,"$langCode.utf8");
		} else {
			setlocale(5, "$langCode.utf8");
			setlocale(6,"$langCode.utf8");
			setlocale(LC_ALL, "$langCode.utf8");
		}
	}
}

function clearLanguageCache($langCode = 'en_US', $domain = 'vvveb') {
	if (function_exists('bindtextdomain')) {
		$locale  = DIR_ROOT . 'locale';
		$nocache = DIR_CACHE . 'nocache';
		$mo      = $locale . DS . $langCode . DS . 'LC_MESSAGES' . DS . "$domain.mo";
		clearstatcache(false, $mo);

		if (function_exists('opcache_reset')) {
			opcache_invalidate($mo, true);
			opcache_reset();
		}

		if (function_exists('symlink') && symlink($locale, $nocache)) {
		bindtextdomain($domain, $nocache);
		textdomain($domain);
		bind_textdomain_codeset($domain, 'different_codeset');
		bindtextdomain($domain, $locale);
		}

		@unlink($nocache);
		@rrmdir($nocache); //for windows if it copies folder instead of link
	}
}

function getLanguage() {
	return session('language', 'en_US');
}

function siteSettings($site_id = SITE_ID) {
	$cache     = System\Cache::getInstance();
	$site      = $cache->cache(APP,'site.' . $site_id,function () use ($site_id) {
		$siteSql             = new Sql\SiteSQL();
		$site                = $siteSql->get(['site_id' => $site_id]);

		if ($site && isset($site['settings'])) {
			$settings = json_decode($site['settings'], true);

			foreach (['favicon', 'logo', 'logo-sticky', 'logo-dark', 'logo-dark-sticky'] as $img) {
				if (isset($settings[$img])) {
					$settings[$img] = System\Images::image($settings[$img], '');
				}
			}

			return $settings;
		}

		return [];
	}, 259200);

	return $site;
}

/**
 * Check php code for syntax errors, return false on errors.
 * 
 * @param string $source 
 * @return bool 
 */
function checkPhpSyntax($source) {
	$tokens = false;

	try {
		$tokens = token_get_all($source, TOKEN_PARSE);
	} catch (\ParseError $e) {
	}

	return $tokens ? true : false;
}

function truncateWords($text, $limit) {
	return preg_replace('/((\w+\W*){' . ($limit - 1) . '}(\w+))(.*)/m', '${1}', $text);
}

/**
 * Send email using template.
 * 
 * @param string|array $to
 * @param mixed $template
 * @param string $subject
 * @param array $data
 * @param mixed $config
 * @return bool 
 */
function email($to, $subject, $template, $data = [], $config = []) {
	$email = System\Email::getInstance();

	if (is_array($template)) {
		$html = $template['html'] ?? '';
		$txt  = $template['txt'] ?? '';
	} else {
		$htmlView  = new System\Core\View();
		$htmlView->setTheme();
		$htmlView->set($data);
		//get email html template
		$htmlView->template("email/$template.html");
		$html = $htmlView->render(true, false, true);

		//get email text template
		$txtView  = new System\Core\View();
		$txtView->setTheme();
		$txtView->set($data);
		$txtView->template("email/$template.txt.html");
		$txt = $txtView->render(true, false, true);
		$txt = htmlToText($txt);
	}

	//get site contact email for sender and reply to
	$site   = siteSettings();
	$sender = $config['sender'] ?? $site['title'];
	$from   = $config['from'] ?? $site['contact-email'];
	$reply  = $config['reply'] ?? $site['contact-email'];

	$email->setHtml($html);
	$email->setText($txt);
	$email->setTo($to);
	$email->setSender($sender);
	$email->setFrom($from);
	$email->setReplyTo($reply);
	$email->setSubject($subject);

	return $email->send();
	//return  file_put_contents(DIR_ROOT . 'mail.html', $html);
}

/* Recursive rmdir */
function rrmdir($src, $skip = []) {
	if (! is_dir($src)) {
		return false;
	}

	$dir = @opendir($src);

	if ($dir) {
		while (false !== ($file = readdir($dir))) {
			$full = $src . DS . $file;

			if (($file != '.') &&
				($file != '..')/* &&
				(! in_array($full, $skip))*/) {
				if (is_dir($full)) {
					rrmdir($full);
				} else {
					unlink($full);
				}
			}
		}
		
		closedir($dir);
		return rmdir($src);
	}
	
	return false;
}

/* Recursive copy */
function rcopy($src, $dst, $skip = [], $overwrite = true) {
	if ($overwrite && file_exists($dst)) {
		//rrmdir($dst);
	}

	if (is_dir($src)) {
		if (! file_exists($dst)) {
			mkdir($dst);
		}
		$files = scandir($src);

		foreach ($files as $file) {
			$full = $src . DS . $file;
			error_log($full);

			if ($file != '.' &&
				$file != '..' &&
				! in_array($file, $skip)) {
				rcopy($full, $dst . DS . $file);
			}
		}
	} else {
		if (file_exists($src)) {
			copy($src, $dst);
		}
	}
}

/* Recursive copy */
function rrename($src, $dst, $skip = []) {
	if (file_exists($dst)) {
		rrmdir($dst);
	}

	if (is_dir($src)) {
		mkdir($dst);
		$files = scandir($src);

		foreach ($files as $file) {
			if ($file != '.' &&
				$file != '..' &&
				! in_array($file, $skip)) {
				//rrename("$src/$file", "$dst/$file");
				rename("$src/$file", "$dst/$file");
			}
		}
	} else {
		if (file_exists($src)) {
			rename($src, $dst);
		}
	}
}

function download($url) {
	$result = false;

	if (function_exists('curl_init')) {
		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_FAILONERROR, true);
		$result = curl_exec($ch);
		curl_close($ch);
	} else {
		if (ini_get('allow_url_fopen') == '1') {
			$context_options = [
				'http' => [
					'timeout'       => 5,
					'ignore_errors' => 1,
				],
			];
			$context  = stream_context_create($context_options);
			$result   = file_get_contents($url, 'r', $context);
		}
	}

	return $result;
}

function getUrl($url, $cache = true, $expire = 0) {
	$cacheDriver = System\Cache :: getInstance();
	$cacheKey    = md5($url);

	if ($cache && ($result = $cacheDriver->get('url', $cacheKey))) {
		return $result;
	} else {
		$result = false;
		//try with file get contents
		if (ini_get('allow_url_fopen') == '1') {
			$context_options = [
				'http' => [
					'timeout'       => 5,
					'ignore_errors' => 1,
				],
			];
			$context         = stream_context_create($context_options);
			$result          = @file_get_contents($url, false, $context);
		}

		if ($result) {
			if ($cache) {
				$cacheDriver->set('url', $cacheKey, $result);
			}

			return $result;
		} else {
			//try with curl
			$ch = curl_init($url);

			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			$result = curl_exec($ch);
			curl_close($ch);

			if ($result) {
				if ($cache) {
					$cacheDriver->set('url', $cacheKey, $result);
				}

				return $result;
			} else {
				throw new \Exception('Curl error: ' . curl_errno($ch) . ' - ' . curl_error($ch));

				return [];
			}
		}
	}
}

function unzip($file) {
	// get the absolute path to $file
	$path = pathinfo(realpath($file), PATHINFO_DIRNAME);

	$zip = new \ZipArchive();
	$res = $zip->open($file);

	if ($res === true) {
		$zip->extractTo($path);
		$zip->close();

		return true;
	}

	return false;
}

function htmlToText($html) {
	$html = preg_replace('/\s+/', ' ', $html);
	$html = str_replace('<br>', "\n", $html);
	$html = str_replace('<p>', "\n<p>", $html);
	$html = str_replace('<h', "\n<h", $html);

	return trim(strip_tags($html));
}

function nl2p($string) {
	$paragraphs = '';

	foreach (explode("\n\n", $string) as $line) {
		if (trim($line)) {
			$paragraphs .= "<p>$line</p>";
		}
	}

	return $paragraphs;
}

function orderStatusBadgeClass($order_status_id = 1) {
	$classes = [
		1 => 'bg-primary-subtle text-body',
		2 => 'bg-success-subtle text-body',
		3 => 'bg-danger-subtle text-body',
		4 => 'bg-success',
		5 => 'bg-danger',
		6 => 'bg-danger',
	];

	return $classes[$order_status_id] ?? 'bg-warning text-dark';
}

function commentStatusBadgeClass($status = 0) {
	$classes = [
		0 => 'bg-primary-subtle text-body', //pending
		1 => 'bg-success-subtle text-body', //approved
		2 => 'bg-danger-subtle text-body', //spam
		3 => 'bg-warning-subtle text-body', //trash
	];

	return $classes[$status] ?? 'bg-secondary-subtle text-dark';
}
