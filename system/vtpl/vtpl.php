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

//set debug to true to show the vtpl console log
defined('VTPL_DEBUG') || define('VTPL_DEBUG', false);

define('VTPL_HTML_MINIFY', false);
define('VTPL_JS_MINIFY', false);
define('VTPL_PHP_MINIFY', false);
define('VTPL_CLEAN_COMP_OPT', false);

define('VTPL_DEBUG_SHOW_XPATH', true);
define('VTPL_DONT_ALLOW_PHP', true); //don't set to false unless ABSOLUTELY NECESSARY!

include 'macros.php';

include 'debug.php';

#[\AllowDynamicProperties]
class Vtpl {
	private $template;

	private $htmlSourceFile;

	private $extension = 'tpl';

	private $translationFunction = '\Vvveb\__';

	private $removeComments = true;

	private $removeWhitespace = false;

	private $removeVattrs = false;

	private $isHTML = true;

	private $checkSyntax = true;

	private $replaceConstants;

	private $constants;

	private $_modifiers = ['outerHTML', 'text', 'before', 'after', 'append', 'prepend', 'deleteAllButFirst', 'deleteAllButFirstChild', 'delete', 'if_exists', 'hide', 'addClass', 'removeClass'];

	private $variableFilters =
	[
		'capitalize'       => 'ucfirst($$0)',
		'cdata'       	    => 'CDATA_START . $$0. CDATA_END',
		'friendly_date'    => 'Vvveb\friendlyDate($$0)',
		'truncate'         => 'substr($$0, 0, $$1)',
		'truncate_words'   => 'Vvveb\truncateWords($$0,$$1)',
		'replace'          => 'str_replace($$1, $$2, $$0)',
		'uppercase'        => 'strtoupper($$0)',
		'lowercase'        => 'strtolower($$0)',
		'append'           => '$$1 . $$0',
		'prepend'          => '$$0 . $$1',
		'strip_html'       => 'strip_tags($$0)',
		'strip_newlines'   => 'str_replace("\n",\' \', $$0)',
		'mod'              => ['tag', 'if (@++$_modc_@@__VTPL_rand()__@@ % (int)$$1 === (int)$$2) {', 'if (@++$_modc_@@__VTPL_rand()__@@ % (int)$$1 === (int)$$2) {'],
		'mod_class'        => ['class', '<?php if (@++$_modc_@@__VTPL_rand()__@@ % (int)$$1 === (int)$$2) echo $$0;?>'],
		'conditional_class'=> ['class', '<?php if (@++$_modc_@@__VTPL_rand()__@@ % (int)$$1 === (int)$$2) echo $$0;?>'],
		'mod_class'        => ['class', '<?php if (@++$_modc_@@__VTPL_rand()__@@ % (int)$$1 === (int)$$2) echo $$0;?>'],
		'iteration_class'  => ['class', '<?php if (@++$_iterc_@@__VTPL_rand()__@@ === (int)$$2) echo $$1;?>'],
		'number_format'    => 'number_format($$0, $$1, $$2, $$3)',
		'only_decimals'    => 'substr($$0, (($_strpos = strrpos($$0, \'.\')) !== false)?$_strpos + 1:-100, ($_strpos !== false)?10:false)',
		'without_decimals' => 'substr($$0, 0, strrpos($$0, \'.\'))',
	];

	private $attributesIndex = 0;

	private $newAttributesIndex = 0;

	private $constantsIndex = 0;

	private $attributes = [];

	private $newAttributes = [];

	private $constatns = [];

	private $_external_elements = false;

	function __construct($selector = null, $componentId = null, $componentContent = null) {
		$this->templatePath = [];

		$this->debug = new VtplDebug();

		if (VTPL_DEBUG) {
			$this->debug->enable(true);
		}

		$this->selector         = $selector;
		$this->componentId      = $componentId;
		$this->componentContent = $componentContent;

		//	libxml_disable_entity_loader();
		$this->document                      = new DomDocument();
		$this->document->preserveWhiteSpace  = true;
		$this->document->recover             = true;
		$this->document->strictErrorChecking = false;
		$this->document->substituteEntities  = false;
		$this->document->formatOutput        = false;
		$this->document->resolveExternals    = false;
		$this->document->validateOnParse     = false;
		$this->document->xmlStandalone       = true;
	}

	function removeVattrs($flag = true) {
		$this->removeVattrs = $flag;
	}

	function addCommand($selector, $command = false) {
		if ($selector) {
			$this->template .= "\n $selector";

			if ($command) {
				$this->template .= " = $command\n";
			}
		}
	}

	function addTemplatePath($path) {
		if ($path) {
			$this->templatePath[] = $path;
		}
	}

	function loadTemplateFile($templateFile) {
		if (file_exists($templateFile)) {
			$this->template .= "\n\n" . file_get_contents($templateFile);
		}
	}

	function loadTemplateFileFromPath($templateFile, $extra = false) {
		foreach ($this->templatePath as $path) {
			$this->debug->log('LOAD', $path . $templateFile);

			if (! file_exists($path . $templateFile)) {
				$this->debug->log('LOAD', '<b>!EXISTS</b>' . $path . $templateFile);

				continue;
			}

			$this->template .= file_get_contents($path . $templateFile);
		}

		if ($extra) {
			$this->template .= $extra;
		}

		if (! $this->template) {
			$this->debug->log('LOAD', '<b>EMPTY</b>' . $path . $templateFile);

			return false;
		}

		/*
		if (function_exists('runkit_lint')) {
			if (! runkit_lint($this->template)) {
				die('There is a php synatx error in ' . $templateFile);
			}
		}
		 */
	}

	private function processPhpcode() {
		/**
			 * Placeholders, replace variables, php code etc with placeholders.
			 *
			 */
		//$this->template = preg_replace('@\/\/[^\n\r]+?(?:\*\)|[\n\r])@','', $this->template);
		//preg_match_all('/(?<!["\'])\/\*.*?\*\/|\s*(?<!["\'])\/\/[^\n]*/s', $this->template, $comments);
		preg_match_all('/(?<!["\'])<\?php(.*?)\?>/s', $this->template, $phpCode);
		//preg_match_all("/([\"'])[^\\\\]*?(\\\\.[^\\\\]*?)*?\\1/s", $str, $matches);

		$phpCode[0] = array_values($phpCode[0]);

		for ($i=0; $i < count($phpCode[1]); $i++) {
			$patternsPhp[]    = '/' . preg_quote($phpCode[0][$i], '/') . '/';
			$placeholdersPhp[]="replace_php_code-$i";
			// double backslashes must be escaped if we want to use them in the replacement argument
			$phpCode[0][$i] = str_replace('\\\\', '\\\\\\\\', $phpCode[1][$i]);
		}

		if (isset($placeholdersPhp)) {
			$this->template = preg_replace($patternsPhp, $placeholdersPhp, $this->template);
		}

		$this->phpCode   = $phpCode[0];
	}

	private  function processFroms() {
		/*
		 *Froms - from(index.html|#element)
		 */
		preg_match_all('/from\(([^\|]+)\|(.+)\)/', $this->template, $froms);

		$froms[0] = array_values($froms[0]);

		for ($i=0; $i < count($froms[1]); $i++) {
			$patternsFroms[] = '/' . preg_quote($froms[0][$i], '/') . '/';
			//  $patterns[]	= preg_quote($matches[0][$i], '/');
			$placeholdersFroms[]="replace_from-$i\n";
			// double backslashes must be escaped if we want to use them in the replacement argument
			$froms[0][$i] = str_replace('\\\\', '\\\\\\\\', $froms[1][$i]);
		}

		if ($froms[0]) {
			$this->template = preg_replace($patternsFroms, $placeholdersFroms, $this->template);
		}

		$this->froms     = $froms;
	}

	private  function processStrings() {
		/* strings */
		//single quote
		preg_match_all("/=\s+'[^'\\\r\n]*(?:\\.[^'\\\r\n]*)*'(?!\])/s", $this->template, $stringsSingle);
		//preg_match_all("/=\s*'[^']+'\s*$/s", $this->template, $stringsSingle);
		//doube quote
		preg_match_all('/=\s+"[^"\\\r\n]*(?:\\.[^"\\\r\n]*)*"(?!\])/s', $this->template, $strings);
		//preg_match_all('/=\s*"[^"]+"\s*$/s', $this->template, $strings);

		$strings       = array_values(array_unique($strings[0]));
		$stringsSingle = array_values(array_unique($stringsSingle[0]));
		$strings       = array_merge((array)$strings, (array)$stringsSingle);

		for ($i=0; $i < count($strings); $i++) {
			$string            = trim($strings[$i], '= ');
			$patternsStrings[] = '/' . preg_quote($string, '/') . '/';
			//  $patterns[]	= preg_quote($matches[0][$i], '/');
			$placeholdersStrings[]="replace_string-$i";
			// double backslashes must be escaped if we want to use them in the replacement argument
			$strings[$i] = str_replace('\\\\', '\\\\\\\\', $string);
		}

		if ($strings) {
			$this->template = preg_replace($patternsStrings, $placeholdersStrings, $this->template);
		}

		$this->strings   = $strings;
	}

	private function processVariables() {
		//preg_match_all('/(?<!["\'\[])(\\$[a-zA-Z0-9->\[\]\'"_\(\)\$\:]*)/s', $this->template, $variables);
		preg_match_all('/(?<!["\'\[])(\\$.+)/', $this->template, $variables);

		$variables[0] = array_values($variables[0]);

		for ($i=0; $i < count($variables[1]); $i++) {
			$patternsVariables[]    = '/' . preg_quote($variables[0][$i], '/') . '/';
			$placeholdersVariables[]="replace_variable-$i";
			// double backslashes must be escaped if we want to use them in the replacement argument
			$variables[0][$i] = str_replace('\\\\', '\\\\\\\\', $variables[1][$i]);
		}

		if ($variables[0]) {
			$this->template = preg_replace($patternsVariables, $placeholdersVariables, $this->template);
		}

		$this->variables = $variables[0];
	}

	private function processTemplateFile() {
		/*
		 * imports - import(common.tpl)
		 *
		 * */
		if (! $this->template || ! $this->xpath) {
			return;
		}
		$foundImports = true;
		//expand imports
		while ($foundImports) {
			$foundImports = preg_match_all("/import\(([^\&%'`\@{}~!#\(\)&\^\+,=\[\]]*?\.$this->extension)\,?(.+)?\);?/", $this->template, $imports);

			for ($i=0; $i < count($imports[0]); $i++) {
				$content       = '';
				$importContent = '';

				foreach ($this->templatePath as $path) {
					$importFile = $path . $imports[1][$i];

					if (file_exists($importFile)) {
						$parameter = trim($imports[2][$i]);
						$this->debug->log('LOAD', $path . $imports[1][$i]);

						if (! empty($parameter)) {
							//if php array then parameter is a template variables list
							if ($parameter[0] == '{') {
								$importContent = file_get_contents($importFile);

								//process template
								$templateParams = json_decode($parameter, true);

								foreach ($templateParams as $name => $value) {
									$importContent = str_replace('{{' . $name . '}}' , $value, $importContent);
								}
							} else {
								//parameter is a css selector to check if the element exists and conditionally include template
								$elements = $this->xpath->query($this->cssToXpath($parameter));
								//remove true and process froms earlier
								if (true || $elements && $elements->length) {
									//found, load template below
									$importContent = file_get_contents($importFile);
								} else {
									//not found, replace import with nothing
									$this->template = str_replace($imports[0][$i], '' , $this->template);

									continue;
								}
							}
						} else {
							$importContent = file_get_contents($importFile);
						}

						//remove comments
						$importContent = preg_replace("/(?<![\"'])\/\*.*?\*\/|\s*(?<![\"'])\/\/[^\n]*/s", '', $importContent);
						$content .= $importContent . "\n";
					} else {
						$this->debug->log('VTPL_IMPORT_FILE_NOT_EXIST', $importFile);
						//error_log($imports[0][$i] . " $importFile does not exists");
					}
				}

				$this->template = str_replace($imports[0][$i], $content, $this->template);
			}
		}

		$this->processPhpcode();
		$this->processVariables();
		$this->processFroms();
		$this->processStrings();

		//remove comments
		$this->template = preg_replace("/(?<![\"'])\/\*.*?\*\/|\s*(?<![\"'])\/\/[^\n]*/s", '', $this->template);
		$this->template = preg_replace('/\n+/',"\n", $this->template);
		$this->template = preg_replace('/(?<=\=)\s*\n/','', $this->template);

		$this->template = str_replace("\n\n","\n",trim($this->template));
		$lines          = explode("\n", $this->template);

		foreach ($lines as $line) {
			$matches = [];
			//check if "=" exists for pair
			if (preg_match('/(.*?)(=)\s*(replace_.*|true|false);?/s', $line, $matches) && $matches[1]) {
				$this->selectors[] = [trim($matches[1]), trim($matches[3])];
			} else {
				//single command, no pair
				$line = trim($line);

				if ($line) {
					$this->selectors[] = [$line];
				}
			}
		}
	}

	/**
	 * Convert a CSS-selector into an xPath-query.
	 *
	 * @return    string
	 * @param    string $selector    The CSS-selector
	 */
	function cssToXpath($selector) {
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
		$this->debug->log('CSS_XPATH_TRANSFORM', $result);

		return $result;
	}

	private function processElements($modifier, $elements, $val) {
		switch ($modifier) {
			case 'deleteAllButFirstChild':
				$this->deleteAllButFirstChild($elements, $val ?? false);

			break;

			case 'deleteAllButFirst':
				$this->deleteAllButFirst($elements);

			break;

			case 'outerHTML':
				$this->outerHTML($elements, $val);

			break;

			case 'innerText':
				$this->innerText($elements, $val);

			break;

			case 'before':
				$this->insertBefore($elements, $val);

			break;

			case 'after':
				$this->insertAfter($elements, $val);

			break;

			case 'append':
				$this->append($elements, $val);

			break;

			case 'prepend':
				$this->prepend($elements, $val);

			break;

			case 'delete':
				$this->delete($elements);

			break;

			case 'if_exists':
				$this->ifExists($elements, $val);

			break;

			case 'hide':
				$this->hide($elements, $val);

			break;

			case 'addClass':
				$this->addClass($elements, $val);

			break;

			case 'removeClass':
				$this->removeClass($elements, $val);

			break;

			case 'addNewAttribute':
				$this->addNewAttribute($elements, $val);

			break;

			case '':
				$this->innerHTML($elements, $val);

			break;

			default:
				$this->setAttribute($elements, $modifier, $val);
		}
	}

	private function processTemplate() {
		if (isset($this->selectors) && isset($this->document) && isset($this->xpath)) {
			//check for multiple selectors
			$newSelectors = [];

			foreach ($this->selectors as &$data) {
				//$data[0] = selector
				if (strpos($data[0], ',') !== false) {
					$selectors = explode(',', $data[0]);
					//set first selector for current selector
					$data[0] = $selectors[0];
					unset($selectors[0]);
					//add new selectors
					foreach ($selectors as $selector) {
						$newSelectors[] = [trim($selector), $data[1]];
					}
				}
			}

			$this->selectors = array_merge($this->selectors, $newSelectors);

			foreach ($this->selectors as &$data) {
				$selector                 = $data[0];
				$selectorComponents       = explode('|', $selector);
				$selector                 = $selectorComponents[0];
				$modifier                 = (isset($selectorComponents[1])) ? trim($selectorComponents[1]) : '';
				$value                    = (isset($data[1])) ? $data[1] : '';
				$this->_external_elements = false;

				//enable disable debugging
				if (! $selector) {
					continue;
				}
				$isConstant = false;

				if (strpos($selector, '@@_CONSTANT_') !== false) {
					$isConstant = true;
				}

				$prefix   = &$this->prefix;
				$isPrefix = false;

				//get all set prefix and save values
				//@my-prefix = #selector .class[attribute]
				$selector = preg_replace_callback('/^@([a-zA-Z][a-zA-Z0-9_-]+)(?![a-zA-Z0-9_-])\s+=\s+(.+)$/',
					function ($matches) use (&$prefix, &$isPrefix) {
						if (isset($matches[1]) && isset($matches[2])) {
							$name = $matches[1];
							$value = $matches[2];

							$prefix[$name] = $value;
							$isPrefix = true;
						}

						return trim($matches[0]);
					}, $selector);

				//if the line is only for set prefix then skip further processing
				if ($isPrefix) {
					continue;
				}
				//replace all prefix with actual values
				$selector = preg_replace_callback('/@([a-zA-Z][a-zA-Z0-9_-]+)(?![a-zA-Z0-9_-])/',
					function ($matches) use (&$prefix) {
						if (isset($matches[1])) {
							$name = $matches[1];

							if (isset($prefix[$name])) {
								return $prefix[$name];
							}
						}

						return trim($matches[0]);
					}, $selector);

				$this->debug->log('SELECTOR', $selector);

				$val = $value;

				if ($selector == 'debug') {
					$this->debug->log = ($value == 'true') ? true : false;
				} else {
					$valueElements = explode('-', $value);

					switch ($valueElements[0]) {
						case 'replace_string':
							$val   = $value;
							$index = (int) $valueElements[1];

							if (isset($this->strings[$index])) {
								$val = trim($this->strings[$index],'"\'');
								$this->debug->log('SELECTOR_STRING', $this->strings[$index]);
							} else {
								$this->debug->log('SELECTOR_STRING', "$index not found for $value");
							}

						break;

						case 'replace_php_code':
							$phpCode = $this->phpCode[(int) $valueElements[1]] ?? '';
							/*
							if (VTPL_PHP_MINIFY === true) {
								$phpCode = $this->minifyPhp($phpCode);
							}
							*/

							if ($modifier && ! in_array($modifier, $this->_modifiers)) {
								$val = '<_script language="php"><![CDATA[' . $this->minifyPhp($phpCode) . ']]></_script>';
							} else {
								if ($modifier == 'if_exists' || $modifier == 'hide') {
									$val = "($phpCode)";
								} else {
									if ($isConstant || $modifier == 'addClass') {
										$val = '<_script language="php"><![CDATA[' . $this->minifyPhp($phpCode) . ']]></_script>';
									} else {
										$val = '<_script language="php"><![CDATA[' . $this->minifyPhp($phpCode) . ']]></_script>';
									}
								}
							}
							$this->debug->log('SELECTOR_PHP', $phpCode);

						break;

						case 'replace_variable':
							if ($modifier) {
								if ($modifier == 'if_exists' || $modifier == 'hide') {
									$val = $this->variables[(int) $valueElements[1]];
								} else {
									if (! in_array($modifier, $this->_modifiers)) {
										$val = '<_script language="php"><![CDATA[if (isset(' . $this->variables[(int) $valueElements[1]] . ')) echo htmlentities(' . $this->variables[(int) $valueElements[1]] . ');]]></_script>';
									}
								}
							} else {
								if ($isConstant) {
									$val = '<_script language="php"><![CDATA[if (isset(' . $this->variables[(int) $valueElements[1]] . ')) echo ' . $this->variables[(int) $valueElements[1]] . ';]]></_script>';
								} else {
									$val = '<_script language="php"><![CDATA[if (isset(' . $this->variables[(int) $valueElements[1]] . ')) echo htmlentities(' . $this->variables[(int) $valueElements[1]] . ');]]></_script>';
								}
							}
							$this->debug->log('SELECTOR_VARIABLE', $this->variables[(int) $valueElements[1]]);

						break;

						case 'replace_from':
							$from = $this->froms[0][(int) $valueElements[1]]; //external html file
							/*
							  $fromSelector = substr($this->froms[2][(int) $valueElements[1]],1);
							  //load specified selector if available otherwise load html with the same selector
							  if (empty($fromSelector))
							  {
							  //override default selector with the provided one
							  $fromSelector = $selector;
											
							  }*/
							//get html
							//if ($from != '@_SELF_@')
							$this->_external_elements = true;
							$val                      = $valueElements;
							//$val = $this->loadFromExternalHtml($from, $fromSelector);
						break;
					}

					if ($isConstant) {
						$this->constants[$selector] = $val;

						continue;
					}

					//echo $selector . ' --- ' . $this->cssToXpath($selector) . "<br/>\n";
					$xpathSelector = $this->cssToXpath($selector);
					$elements      = $this->xpath->query($xpathSelector);

					if (! $elements) {
						$this->debug->log(0, ' [empty]');
					} else {
						if ($elements && $elements->length == 0) {
							$this->debug->log(0, ' [0 elements]');
						} else {
							$this->debug->log(0, " [{$elements->length} elements]");
						}
					}

					$this->debug->log('SELECTOR_VARIABLE', $selector . ' - ' . $modifier);

					$this->processElements($modifier, $elements, $val);
				}
			}
		}
	}

	/*
	 Process data-filter-* attributes defined in filters array.
	 Process attributes with json and transforms them to php arrays ex: data-my-data='{var:"value"}'
	 Process macro definitions like @@macro Mymacro('var1', 'var2') Mymacro must be a function defined with name vtplMymacro and must accept two variables like in definition
	 Process json path, for a node with data-v-myjson='{var:{subvar1:val1, subvar2:val2}}' @@myjson.var.subvar1@@ will return val1
	 */

	private function processAttributeFilters($value, &$node) {
		//filters
		//search for filters and their options
		//@filter_([^ :$]+):?(\'[^\']+\'|[^ $]+)?@ old
		$filters = [];
		$length  = $node->attributes->length;

		for ($i = 0; $i < $length; ++$i) {
			if ($item = $node->attributes->item($i)) {
				$name = $item->name;

				if (strpos($name, 'data-filter') !== false) {
					$name           = str_replace('data-filter-', '', $name);
					$filters[$name] = $item->value;
					$node->removeAttribute($item->name);
				}
			}
		}
		//if ($class && preg_match_all('@filter_([^ :$]+)(:\'[^\']+\'|:[^ $]+)*@', $class, $matches, PREG_SET_ORDER) > 0)
		if ($filters) {
			$chain = '_$variable';

			foreach ($filters as $name => $options) {
				if ($options) {
					//string is json
					if ($options[0] == '{' || $options[0] == '[') {
						$options = json_decode($options, false);
					} else {
						$options = [$options];
					}
				} else {
					$options = [];
				}

				//clean up, remove filter from attribute
				if (isset($this->variableFilters[$name])) {
					$type     = '';
					$commands = $this->variableFilters[$name];

					if (is_array($commands)) {
						$type = $commands[0];
						unset($commands[0]);
					} else {
						$commands = [1 => $commands];
					}

					foreach ($commands as &$command) {
						$commandVariableCount = preg_match_all('@\$\$[1-9]+@' , $command);

						//if different parameter number then don't add filter to filter chain
						if ($commandVariableCount != count($options)) {
							$this->debug->log('warning', 'Invalid number of options for filter <b>' . $name . '</b> for "' . $name . '"');

							continue 2;
						}

						//run php functions if any
						$command = preg_replace_callback('/@@__VTPL_([^_]+)__@@/',
									function ($matches) {
										return eval('return ' . $matches[1] . ';');
									}, $command);
					}

					if ($type == 'class') {
						//replace variables with their values
						$command = preg_replace_callback('@\$\$(\d+)@',
								   function ($matches) use ($options) {
								   	if ($matches[1] > 0) {
								   		$options[$matches[1]] = '\'' . trim($options[$matches[1]], '\'') . '\'';
								   	}

								   	return $options[$matches[1]];
								   }, $command);

						$this->addNodeClass($node, trim($commands[1], '\''), false);
					} else {
						if ($type == 'tag') {
							//replace variables with their values
							$command = preg_replace_callback('@\$\$(\d+)@',
								   function ($matches) use ($options) {
								   	if ($matches[1] > 0) {
								   		$options[$matches[1]] = '\'' . trim($options[$matches[1]], '\'') . '\'';
								   	}

								   	return $options[$matches[1]];
								   }, $command);

							$openTag = preg_replace_callback('@\$\$(\d+)@',
								   function ($matches) use ($options) {
								   	if ($matches[1] > 0) {
								   		$options[$matches[1]] = '\'' . trim($options[$matches[1]], '\'') . '\'';
								   	}

								   	return $options[$matches[1]];
								   }, $commands[1]);

							$closeTag = preg_replace_callback('@\$\$(\d+)@',
								   function ($matches) use ($options) {
								   	if ($matches[1] > 0) {
								   		$options[$matches[1]] = '\'' . trim($options[$matches[1]], '\'') . '\'';
								   	}

								   	return $options[$matches[1]];
								   }, $commands[2]);

							$nodeList = [$node]; //only one node and the methods accepts multiple nodes
							$this->tagWrap($nodeList, $openTag, $closeTag);
						} else {
							if (is_array($options)) {
								array_unshift($options, $chain);
							} else {
								$options[] = $chain;
							}

							$chain = preg_replace_callback('@\$\$(\d+)@',
								   function ($matches) use ($options) {
								   	if ($matches[1] > 0) {
								   		$options[$matches[1]] = '\'' . trim($options[$matches[1]], '\'') . '\'';
								   	}

								   	return $options[$matches[1]];
								   }, $commands[1]);
						}
					}
				} else {
					$this->debug->log('warning','Unknown filter <b>' . $name . '</b> for "' . $name . '"');
				}
			}

			preg_match('@echo htmlentities\(([^)]+)\)@', $value, $variable);

			if ($variable) {
				$chain = str_replace('_$variable', $variable[1], $chain);
				$value = str_replace($variable[0], 'echo htmlentities(' . $chain . ')', $value);
			} else {
				preg_match('@echo\(([^)]+)\)@', $value, $variable);

				if ($variable) {
					$chain = str_replace('_$variable', $variable[1], $chain);
					$value = str_replace($variable[0], 'echo(' . $chain . ')', $value);
				}
			}
		}

		return $value;
	}

	/*
	 Replace placeholders in values 
	 Ex; 
	 // @@__innerText__@@ will be replaced with the text already present in the tag that has data-v-product-name attribute
	 [data-v-product-name] = <?php echo 'Product: @@__innerText__@@';?>  
	 
	 Available placeholders: 
	 @@__innerText__@@            - inner html of the node
	 @@__innerText__@@            - inner text of the node
	 @@__my-attribute__@@         - value of my-attribute of current node
	 @@__data-v-plugin-(.+)__@@   - run regex and return first match \1 for example for data-v-plugin-name it will return `name`
	 @@__my-*:my-(*)__@@ - get attribute name that starts with 'my-' and run the regex after ':' used to extract attribute name from current node
	 
	 Process data-filter-* attributes defined in filters array.
	 Process attributes with json and transforms them to php arrays ex: data-my-data='{var:"value"}'
	 Process macro definitions like @@macro Mymacro('var1', 'var2') Mymacro must be a function defined with name vtplMymacro and must accept two variables like in definition
	 Process json path, for a node with data-v-myjson='{var:{subvar1:val1, subvar2:val2}}' @@myjson.var.subvar1@@ will return val1
	 */
	private function processAttributeConstants($value, $node) {
		if (! $node) {
			return $value;
		}

		$value = preg_replace_callback('/@@__innerText__@@/',
					   function ($matches) use ($node) {
					   	$value = $this->innerHtml([$node]);

					   	if (isset($value[0]) && $value[0] == '{') {
					   		$value = json_decode($value, 1);
					   		$value = var_export($value, 1);
					   	}

					   	$this->debug->log('VTPL_ATTRIBUTE', '<b>VALUE </b>' . $value);

					   	return trim($value);
					   }, $value);

		$value = preg_replace_callback('/@@__innerHtml__@@/',
					   function ($matches) use ($node) {
					   	$value = $this->innerHtml([$node]);

					   	if (isset($value[0]) && $value[0] == '{') {
					   		$value = json_decode($value, 1);
					   		$value = var_export($value, 1);
					   	}

					   	$this->debug->log('VTPL_ATTRIBUTE', '<b>VALUE </b>' . $value);

					   	return $value;
					   }, $value);

		//attribute value
		$value = preg_replace_callback('/@@__([\.a-zA-Z*_-]+)__@@/',
					   function ($matches) use ($node) {
					   	$attributeName = $matches[1];
					   	$value = '';

					   	if (strpos($attributeName, '*') !== false) {
					   		//wildcard attribute
					   		$attributeName = str_replace('*', '', $attributeName);

					   		foreach ($node->attributes as $attribute) {
					   			if (strpos($attribute->name, $attributeName) !== false) {
					   				$value = $attribute->value;
					   			}
					   		}
					   	} else {
					   		$value = $node->getAttribute($matches[1]);
					   	}

					   	if (isset($value[0]) && $value[0] == '{') {
					   		$value = json_decode($value, 1);
					   		$value = var_export($value, 1);
					   	}

					   	$this->debug->log('VTPL_ATTRIBUTE', '<b>VALUE </b>' . $value);

					   	return $value;

					   	return \Vvveb\System\filter('@[#\@&=?\0-9a-zA-Z_: ;-]+@',$value, 500);
					   }, $value);

		//run regex on attribute name @@__data-v-product-*:data-v-product-(*)__@@
		//$value = preg_replace_callback('/@@__\[([\*a-zA-Z_-]+)\]:([a-zA-Z-_\]\[\\\+\(\)\,\+\^:*]+)__@@/',
		$value = preg_replace_callback('/@@__([*a-zA-Z_-]+):([a-zA-Z-_\]\[\\\+\(\)\,\.\+\^:*]+)__@@/',
					   function ($matches) use ($node) {
					   	$attrib = $matches[1];
					   	$regex = $matches[2];
					   	$this->debug->log('VTPL_ATTRIBUTE', '<b>ATTRIB NAME</b> ' . $attrib);
					   	$this->debug->log('VTPL_ATTRIBUTE', '<b>REGEX </b> ' . $regex);
					   	$value = $node->getAttribute($attrib);
					   	$this->debug->log('VTPL_ATTRIBUTE', '<b>ATTRIB VALUE </b> ' . $value);

					   	foreach ($node->attributes as $name => $attrNode) {
					   		if (preg_match('@' . $regex . '@', $value, $_match)) {
					   			//$value = \Vvveb\System\filter('@[0-9a-zA-Z_\-\.\#\/]+@', $_match[1], 500);
					   			$value = $_match[1];
					   			$this->debug->log('VTPL_ATTRIBUTE', '<b>MATCH </b>' . $_match[1]);
					   		} else {
					   			$this->debug->log('VTPL_ATTRIBUTE', '<b>NO MATCH </b> ' . $regex . ' - ' . $attrib . ' - ' . $attrNode->name);
					   		}
					   	}

					   	return $value;
					   }, $value);

		//attribute name ex @@__data-v-plugin-(.+)__@@
		$value = preg_replace_callback('/@@__(.+?)__@@/',
				   function ($matches) use ($node) {
				   	$value = $node->getAttribute($matches[1]);
				   	$this->debug->log('VTPL_ATTRIBUTE', '<b>ATTRIB NAME</b> ' . $matches[1]);
				   	//expand shorthand expression (*) to regex ([a-zA-Z_0-9-]+)
				   	$regex = str_replace('(*)', '([a-zA-Z_0-9-]+)', $matches[1]);

				   	foreach ($node->attributes as $name => $attrNode) {
				   		if (preg_match("@$regex@", $name, $_match)) {
				   			//$value = \Vvveb\System\filter('@[0-9a-zA-Z_\-\.\#\/]+@', $_match[1], 500);
				   			$value = $_match[1] ?? null;
				   			$this->debug->log('VTPL_ATTRIBUTE', '<b>MATCH </b>' . $value);
				   		} else {
				   			$this->debug->log('VTPL_ATTRIBUTE', '<b>NO MATCH </b>');
				   		}
				   	}

				   	return $value;

				   	return \Vvveb\System\filter('@[#\@&=?\0-9a-zA-Z_: ;-]+@',$value, 500);
				   }, $value);

		$json = [];

		//check if attribute value is json string
		if ($node->hasAttributes()) {
			foreach ($node->attributes as $attr) {
				$name = str_replace('data-v-', '', $attr->nodeName);
				$val  = $attr->nodeValue;

				if ($val && $val[0] == '{') {
					$json[$name] = json_decode($val, true);
				}
			}
		}

		$value = preg_replace_callback('/@@([\.a-zA-Z_-]+)@@/m',
					   function ($matches) use ($node, $json) {
					   	return $attrib = var_export(\Vvveb\System\arrayPath($json, $matches[1]), true);
					   	$this->debug->log('VTPL_ATTRIBUTE', '<b>JSON NAME</b> ' . $attrib);
					   	$this->debug->log('VTPL_ATTRIBUTE', '<b>REGEX </b> ' . $regex);
					   	$value = $node->getAttribute($attrib);
					   	$this->debug->log('VTPL_ATTRIBUTE', '<b>ATTRIB VALUE </b> ' . $value);

					   	return $value;
					   }, $value);

		//macros, compile time function calls
		$value = preg_replace_callback('/@@macro ([a-z_A-Z]+)\((.+?)\)@@/',
					   function ($matches) use (&$node) {
					   	$function = 'vtpl' . $matches[1];
					   	//$parameters = preg_split('@\'?\s*,\s*\'?@', $matches[2]);

					   	if (function_exists($function)) {
					   		preg_match_all('@"(.*?)",?@i', $matches[2], $parameters, PREG_SET_ORDER);
					   		//add node as first parameter to allow macros to alter node if needed
					   		$params[] = &$this;
					   		$params[] = &$node;

					   		foreach ($parameters as $param) {
					   			$params[] = trim($param[1]);
					   		}
					   		/*$params_exp = var_export($params, true);*/
					   		return call_user_func_array($function, $params);
					   	}

					   	return $matches[0];
					   }, $value);

		$value = $this->processAttributeFilters($value, $node);

		return $value;
	}

	private function removeChildren(&$node) {
		while ($node->firstChild) {
			while ($node->firstChild->firstChild) {
				$this->removeChildren($node->firstChild);
			}
			$node->removeChild($node->firstChild);
		}
	}

	private function innerHTML($nodeList, $html = false) {
		if ($nodeList) {
			foreach ($nodeList as $node) {
				if ($html === false) {
					$doc = new DOMDocument();

					foreach ($node->childNodes as $child) {
						$doc->appendChild($doc->importNode($child, true));
					}

					if ($this->isHTML) {
						return $doc->saveHTML();
					} else {
						return $doc->saveXML();
					}
				} else {
					if ($html == '') {
						continue;
					}
					//if ($node->nodeName !== 'title') $html .= '<_script language="php"><![CDATA[/*__VTPL_MAP:' . $node->getLineNo() . '*/]]></_script>';

					if ($this->_external_elements) {
						$result = $this->loadFromExternalHtml($html, $node);
						$this->removeChildren($node);

						foreach ($result as $externalNode) {
							$importedNode = $this->document->importNode($externalNode, true);
							$node->appendChild($importedNode);
						}
					} else {
						switch ($node->nodeName) {
							case 'input':
				/*		    case 'option':*/
								$this->setAttribute($node, 'value', $html);

							break;
							//case 'img':
							case 'iframe':
							case 'script':
							case 'video':
							case 'audio':
							case 'source':
								$this->setAttribute($node, 'src', $html);

							break;
							/*
							//case 'a':
							case 'link':
							//case 'script':
								$this->setAttribute($node, 'href', $html);
							break;
							*/

							case 'form':
								$this->setAttribute($node, 'action', $html);

							break;

							default:
								$this->removeChildren($node);
								$f      = $this->document->createDocumentFragment();
								$append = $this->processAttributeConstants($html, $node);
								$f->appendXML($append);
								$node->appendChild($f);
							}
					}
				}
			}
		}
	}

	private function outerHTML(&$nodeList, $html = false) {
		foreach ($nodeList as $node) {
			if ($html === false) {
				$doc = new DOMDocument();

				foreach ($node->childNodes as $child) {
					$node->parentNode->replaceChild($doc->importNode($child, true), $node);
				}

				if ($this->isHTML) {
					return $doc->saveHTML();
				} else {
					return $doc->saveXML();
				}
			} else {
//				$this->removeChildren($node);
				if ($html == '') {
					continue;
				}

				if ($this->_external_elements) {
					$result = $this->loadFromExternalHtml($html, $node);

					$parent = $node->parentNode;
					//$children = $node->childNodes;
					$count  = 0;

					if ($result) {
						foreach ($result as $externalNode) {
							$importedNode = $this->document->importNode($externalNode, true);

							if ($parent) {
								if ($count) {
									$parent->appendChild($importedNode);
								} else {
									$parent->replaceChild($importedNode, $node);
									/*
									foreach ($children as $child) {
										$importedNode->appendChild($child);
									}
									 */
								}
								$node = $importedNode;
								$count++;
							}
						}
					}
				} else {
					$f = $this->document->createDocumentFragment();
					$f->appendXML($this->processAttributeConstants($html, $node));
					$node->parentNode->replaceChild($f, $node);
				}
			}
		}
	}

	private function innerText($nodeList, $text = false) {
		foreach ($nodeList as $node) {
			if ($text === false) {
				return $node->nodeValue;
			} else {
				if ($node->hasChildNodes()) {
					foreach ($node->childNodes as $childNode) {
						$value = trim($childNode->nodeValue);
						//find first non empty text node
						//error_log(XML_TEXT_NODE . ' - ' . $childNode->nodeType . ' - ' . !empty($value) );
						if ($childNode->nodeType == XML_TEXT_NODE && ! empty($value)) {
							$f = $this->document->createDocumentFragment();
							//error_log("innerText = $text");
							$f->appendXML($this->processAttributeConstants($text, $node));

							$node->replaceChild($f, $childNode);

							break;
						}
					}
				} else {
					switch ($node->nodeName) {
							case 'input':
				/*		    case 'option':*/
								$this->setAttribute($node, 'value', $text);

							break;

							break;
							//case 'img':
							case 'iframe':
							case 'script':
							case 'video':
							case 'audio':
							case 'source':
								$this->setAttribute($node, 'src', $text);

							break;
							/*
							//case 'a':
							case 'link':
								$this->setAttribute($node, 'href', $text);
							break;
							*/

							case 'form':
								$this->setAttribute($node, 'action', $text);

							break;

							default:
							//if node has no children append text
							$f = $this->document->createDocumentFragment();
							$f->appendXML($this->processAttributeConstants($text, $node));
							$node->appendChild($f);
					}
				}
			}
		}
	}

	/*
	 * Show elements conditionally if $variable is true
	 * 
	 * @param mixed $nodeList 
	 * @param mixed $variable [false] 
	 *
	 * @return mixed 
	 */
	private function ifExists(&$nodeList, $variable = false) {
		if ($variable == '') {
			return false;
		}

		foreach ($nodeList as $node) {
			$condition = $this->processAttributeConstants($variable, $node);
			$isset     = str_replace('!', '', $condition);
			//before
			$html = "<_script language=\"php\"><![CDATA[if (isset($isset) && $condition) {]]></_script>";
			$f    = $this->document->createDocumentFragment();
			$f->appendXML($html);
			$node->parentNode->insertBefore($f, $node);

			//after
			$html = '<_script language="php">}</_script>';
			$f    = $this->document->createDocumentFragment();
			$f->appendXML($html);
			//$node->parentNode->appendChild( $f );
			$node->parentNode->insertBefore($f, $node->nextSibling);
		}
	}

	/**
	 * Opposite of ifExists, hides the elements if $variable is true.
	 * 
	 * @param mixed $nodeList 
	 * @param mixed $variable [false] 
	 *
	 * @return mixed 
	 */
	private function hide(&$nodeList, $variable = false) {
		if ($variable) {
			$variable = '!' . $variable;
		}

		return $this->ifExists($nodeList, $variable);
	}

	private function tagWrap(&$nodeList, $open = false, $close = false) {
		if ($open == '' || $close == '') {
			return false;
		}
		$openStart = "<_script language=\"php\"><![CDATA[$open]]></_script>";
		$openEnd   = '<_script language="php">}</_script>';

		$closeStart = "<_script language=\"php\"><![CDATA[$close]]></_script>";
		$closeEnd   = '<_script language="php">}</_script>';

		foreach ($nodeList as $node) {
			//before start
			$f = $this->document->createDocumentFragment();
			$f->appendXML($this->processAttributeConstants($openStart, $node));
			$node->parentNode->insertBefore($f, $node);

			//before end
			$f = $this->document->createDocumentFragment();
			$f->appendXML($this->processAttributeConstants($openEnd, $node));

			if ($node->hasChildNodes()) {
				$node->insertBefore($f,$node->firstChild);
			} else {
				$node->appendChild($f);
			}

			//after start
			$f = $this->document->createDocumentFragment();
			$f->appendXML($this->processAttributeConstants($closeStart, $node));
			$node->appendChild($f);

			//after end
			$f = $this->document->createDocumentFragment();
			$f->appendXML($this->processAttributeConstants($closeEnd, $node));
			$node->parentNode->insertBefore($f, $node->nextSibling);
		}
	}

	private function insertBefore(&$nodeList, $html = false) {
		if ($html == '') {
			return false;
		}

		if ($nodeList) {
			foreach ($nodeList as $node) {
				$f = $this->document->createDocumentFragment();
				$f->appendXML($this->processAttributeConstants($html, $node));
				$node->parentNode->insertBefore($f, $node);
			}
		}
	}

	private function insertAfter(&$nodeList, $html = false) {
		if ($html == '') {
			return false;
		}

		if ($nodeList) {
			foreach ($nodeList as $node) {
				$f = $this->document->createDocumentFragment();
				$f->appendXML($this->processAttributeConstants($html, $node));
				//$node->parentNode->appendChild( $f );
				$node->parentNode->insertBefore($f, $node->nextSibling);
			}
		}
	}

	private function append(&$nodeList, $html = false) {
		if ($html == '') {
			return false;
		}

		if ($nodeList) {
			foreach ($nodeList as $node) {
				if ($this->_external_elements) {
					if ($this->froms[0][(int) $html[1]] == '@_SELF_@') {
						$selector = $this->froms[2][(int) $html[1]];
						$xpath    = new DOMXpath($this->document);
						$result   = $xpath->query($this->cssToXpath($selector));
					} else {
						$result = $this->loadFromExternalHtml($html, $node);
					}

					if (! $result) {
						continue;
					}
					//$html = array_reverse($html);
					foreach ($result as $externalNode) {
						$importedNode = $this->document->importNode($externalNode, true);
						$node->appendChild($importedNode);
					}
				} else {
					$f = $this->document->createDocumentFragment();
					$f->appendXML($html);
					$node->appendChild($f);
				}
			}
		}
	}

	private function prepend(&$nodeList, $html = false) {
		if ($html == '') {
			return false;
		}

		if ($nodeList) {
			foreach ($nodeList as $node) {
				if ($this->_external_elements) {
					$result = $this->loadFromExternalHtml($html, $node);

					if (! $result) {
						continue;
					}

					if (is_array($result)) {
						$result = array_reverse($result);
					}

					foreach ($result as $externalNode) {
						$importedNode = $this->document->importNode($externalNode, true);

						if ($node->firstChild) {
							// $ref has an immediate sibling : insert newnode before this one
							$node->insertBefore($importedNode, $node->firstChild);
						} else {
							// $ref has no sibling next to him : insert newnode as last child of his parent
							$node->appendChild($importedNode);
						}
					}
				} else {
					$f = $this->document->createDocumentFragment();
					$f->appendXML($html);

					if ($node->firstChild) {
						// $ref has an immediate sibling : insert newnode before this one
						$node->insertBefore($f, $node->firstChild);
					} else {
						// $ref has no sibling next to him : insert newnode as last child of his parent
						$node->appendChild($f);
					}
				}
			}
		}
	}

	private function deleteAllButFirst(&$nodeList, $parent = false) {
		$first = true;

		if ($nodeList) {
			foreach ($nodeList as $node) {
				if (! $first) {
					$this->removeChildren($node);

					if ($node->parentNode) {
						$node->parentNode->removeChild($node);
					}
				}
				$first = false;
			}
		}
	}

	private function deleteAllButFirstChild(&$nodeList, $parent = false) {
		$parents = [];

		if ($nodeList) {
			foreach ($nodeList as $node) {
				if (in_array($node->parentNode, $parents, true)) {
					$this->removeChildren($node);
					$node->parentNode->removeChild($node);
				}

				if ($node->parentNode) {
					$parents[] = $node->parentNode;
				}
			}
		}
	}

	private function delete(&$nodeList) {
		foreach ($nodeList as $node) {
			$this->removeChildren($node);

			if ($node->parentNode) {
				$node->parentNode->removeChild($node);
			}
		}
	}

	private function setNodeAttribute($node, $attribute, $val) {
		if (! $node || ! $val) {
			return;
		}
		$value = $this->processAttributeConstants($val, $node);
		//if the attribute value has no php in it add it directly
		if ($value) {
			if (strpos($value, '<_script') === false) {
				$node->setAttribute($attribute, $value);
			} else {
				$this->attributes[++$this->attributesIndex] = $value;
				$node->setAttribute($attribute, "@@__VTPL__ATTRIBUTE_PLACEHOLDER__{$this->attributesIndex}@@");
			}
		}
	}

	private function setAttribute(&$nodeList, $attribute, $val) {
		if (is_a($nodeList,'DOMNodeList')) {
			if ($nodeList->length > 0) {
				foreach ($nodeList as $node) {
					/*			$attr = new DOMAttr($attribute);
								$attr->value = $val;
								$node->setAttributeNodeNS($attr);*/

					$this->setNodeAttribute($node, $attribute, $val);
				}
			}
		} else {
			$this->setNodeAttribute($nodeList, $attribute, $val);
		}
	}

	private function addNodeClass(&$node, $val, $processConstants = true) {
		if ($processConstants) {
			$val =  $this->processAttributeConstants($val, $node);
		}

		$this->attributes[++$this->attributesIndex] = $val;
		$node->setAttribute('class', $node->getAttribute('class') . " @@__VTPL__ATTRIBUTE_PLACEHOLDER__{$this->attributesIndex}@@");
	}

	private function addClass(&$nodeList, $val) {
		if (is_a($nodeList,'DOMNodeList')) {
			if ($nodeList->length > 0) {
				foreach ($nodeList as $node) {
					$this->addNodeClass($node, $val);
				}
			}
		}
	}

	private function addNewAttribute(&$nodeList, $val) {
		if ($nodeList && $nodeList->length > 0) {
			foreach ($nodeList as $node) {
				$this->newAttributes[++$this->newAttributesIndex] = $this->processAttributeConstants($val, $node);
				$node->setAttribute("__VTPL__NEW_ATTRIBUTE_PLACEHOLDER__{$this->newAttributesIndex}",'');
			}
		}
	}

	private function removeClass(&$nodeList, $val) {
		if ($nodeList->length > 0) {
			foreach ($nodeList as $node) {
				$class = $node->setAttribute('class');
				$class = str_replace($val, '', $class);
				$node->setAttribute('class', $class);
			}
		}
	}

	private function loadFromExternalHtml($val, $node) {
		$filename = $this->froms[0][(int) $val[1]]; //external html file
		$selector = $this->froms[2][(int) $val[1]];
		//load specified selector if available otherwise load html with the same selector

		$filename = $this->processAttributeConstants($filename, $node);
		$selector = $this->processAttributeConstants($selector, $node);

		if (substr_compare($filename,'/plugins/', 0, 9) === 0) {
			$filename = DIR_ROOT . $filename;
		} else {
			if (substr_compare($filename,'/public/', 0, 8) === 0) {
				$filename = DIR_ROOT . $filename;
			} else {
				if ($filename[0] !== '/') {
					$filename = $this->htmlPath . $filename;
				}
			}
		}

		$this->debug->log('SELECTOR_FROM', $filename);

		if (! ($html = @file_get_contents($filename))) {
			Vvveb\log_error("can't load html $filename");
			$this->debug->log('LOAD', '<b>EXTERNAL ERROR</b> ' . $filename . ' ' . $selector);

			return false;
		}

		$this->debug->log('LOAD', $filename . ' <b>SELECTOR</b> ' . $selector);

		if (VTPL_DONT_ALLOW_PHP) {
			$html = $this->removePhp($html);
		}

		if (VTPL_HTML_MINIFY === true) {
			$html = $this->minifyHtml($html);
		}

		$document = new DomDocument();

		if ($this->isHTML) {
			@$document->loadHTML($html);
		} else {
			@$document->loadXML($html);
		}

		$xpath         = new DOMXpath($document);
		$xpathSelector = $this->cssToXpath($selector);
		$elements      = $xpath->query($xpathSelector);

		return $elements;
	}

	function loadHtmlTemplate($filename) {
		$this->isHTML = (substr($filename, -3) != 'xml');

		if (strpos($filename, DS) === false) {
			$filename = $this->htmlPath . $filename;
		}

		if (! ($html = @file_get_contents($filename))) {
			Vvveb\log_error("can't load template $filename");
			$this->debug->log('LOAD', '<b>ERROR</b> ' . $filename);

			return false;
		}
		$this->htmlSourceFile = $filename;

		$this->debug->log('LOAD', $filename);

		if (VTPL_DONT_ALLOW_PHP) {
			$html = $this->removePhp($html);
		}

		//replace script tags with placeholders to preserve formatting.

		//preg_match_all("@<script[^>]*>.*?script>@s", $html, $this->_scripts);
		preg_match_all("/<script((?:(?!src=|data-).)*?)>(.*?)<\/script>/smix", $html, $this->_scripts);

		$this->_scripts = array_values(array_unique($this->_scripts[0]));
		$count          = count($this->_scripts);

		if ($count) {
			for ($i=0; $i < $count; $i++) {
				$patternsScripts[]    = '/' . preg_quote($this->_scripts[$i], '/') . '/';
				$placeholdersScripts[]= '<script holder="@@__VTPL__SCRIPT_PLACEHOLDER__' . $i . '@@"></script>';
				$this->_scripts       = str_replace('\\\\', '\\\\\\\\', $this->_scripts);
			}

			$html = preg_replace($patternsScripts, $placeholdersScripts, $html);
		}

		if (VTPL_HTML_MINIFY === true) {
			$html = $this->minifyHtml($html);
		}
		//replace constants
		if ($this->replaceConstants) {
			$html = str_replace(array_keys($this->replaceConstants),array_values($this->replaceConstants),$html);
		}

		if ($this->isHTML) {
			@$this->document->loadHTML($html);
		} else {
			@$this->document->loadXML($html);
		}

		$errors = libxml_get_errors();

		//original document used to extract selectors
		//$this->originalDocument = clone($this->document);
		$this->xpath = new DOMXpath($this->document);

		if ($this->componentContent) {
			//replace component content from page with the one provided
			$elements = $this->xpath->query($this->cssToXpath($this->selector));

			if ($elements) {
				$node     = $elements->item($this->componentId);

				if ($node && $node->parentNode) {
					//$html = '<div>asdasdasdasd<div>';
					//$html = $this->processAttributeConstants($this->componentContent, $node);

					$tmpDom = new DomDocument();

					if ($this->isHTML) {
						@$tmpDom->loadHTML($this->componentContent);
					} else {
						@$tmpDom->loadXML($this->componentContent);
					}
					$body         = $tmpDom->getElementsByTagName('body');
					$nodeToImport = $body->item(0)->firstChild;

					$importNode = $this->document->importNode($nodeToImport, true);
					//$this->document->appendChild($importNode);
					$node->parentNode->replaceChild($importNode, $node);
					//$node->parentNode->removeChild($node);
					///$node->appendChild($f);
				}
			}
		}

		//add base tag if missing
		$base = $this->document->getElementsByTagName('base');

		if ($base->length == 0) {
			$head = $this->document->getElementsByTagName('head');

			if ($head->length > 0) {
				$base = $this->document->createElement('base');
				$head->item(0)->insertBefore($base, $head->item(0)->firstChild);
			}
		}

		return $errors;
	}

	private function setMultiLanguageText($currentNode) {
		if (! $currentNode || ! $currentNode->childNodes || $currentNode->childNodes->length == 0) {
			return;
		}
		$length = $currentNode->childNodes->length;

		for ($i = 0; $i < $length; $i++) {
			$node = $currentNode->childNodes[$i];

			if (! $node) {
				continue;
			}
			//strip comments
			if ($this->removeComments && $node->nodeType == XML_COMMENT_NODE) {
				$node->parentNode->removeChild($node);
				//$i--;
				//continue;
			}

			//check if attribute is a php variable
			if ($node && $node->hasAttributes()) {
				foreach ($node->attributes as $name => $attrNode) {
					$value = $attrNode->nodeValue;
					$name  = $attrNode->nodeName;

					if ($this->removeVattrs && substr($name, 0, 7) === 'data-v-') {
						$node->removeAttribute($name);

						continue;
					} else {
						if (isset($value[0]) && $value[0] == '$' && preg_match('/^\$[a-z][\w\.]+$/i', $value)) {
							if (strpos($value, 'this') === 0) {
								$value = str_replace('this.', 'this->', $value);
							}
							$value   = Vvveb\dotToArrayKey($value);

							$php  = '<_script language="php"><![CDATA[ if (isset(' . $value . ')) echo ' . $value . ';]]></_script>';
							$this->setNodeAttribute($node, $name, $php);
						}
					}
					/*
					if (strpos($name, 'data-v-') === 0) {
						$node->removeAttribute($name);
					}*/
				}
			}

			if ($node && $node->nodeType == XML_TEXT_NODE &&
				(! isset($node->parentNode->tagName) || $node->parentNode->tagName != '_script')) {
				if (isset($node->wholeText)) {
					$text = $node->wholeText;
				} else {
					$text = $node->textContent;
				}

				$text    = \Vvveb\stripExtraSpaces($text);
				$trimmed = trim($text);

				if (strlen($trimmed) < 2) {
					continue;
				}

				$before = $currentNode->childNodes->length;

				if ($trimmed != '') {
					$trimmed = addcslashes($trimmed, "'");
					$php     = '<_script language="php"><![CDATA[ echo ' . $this->translationFunction . '(\'' . $trimmed . '\');]]></_script>';
					//keep space around text for html spacing
					$php = str_replace($trimmed, $php, $text);
					$f   = $this->document->createDocumentFragment();
					$f->appendXML($php);
					$node = $node->parentNode->replaceChild($f, $node);
				//$node->parentNode->replaceChild($f, $node);
				} else {
					if ($this->removeWhitespace) {
						//remove empty space
						$node->parentNode->removeChild($node);
						$i--;
					}
				}

				$diff = $currentNode->childNodes->length - $before;
				$length += $diff;
			//$i += $diff;
			} else {
				$this->setMultiLanguageText($node);
			}
		}
	}

	private function minifyPhp($php) {
		//php comments outside strings /* */
		$php = preg_replace('/(?<!["\'])\/\*.*?\*\//s', '', $php);

		//php comments outside strings //
		$php = preg_replace('@(\/\/)(?=(?:[^"\']|["\'][^"\']*["\'])*$)[^\n]*@s', '', $php);

		//repeating spaces
		$php = preg_replace('/\s+/', ' ', $php);

		//repeating end lines
		$php = preg_replace('/\n+/', '', $php);

		return $php;
	}

	private function removePhp($html) {
		//hack, php allows different opening and closing tags
		$html = preg_replace('@(<\?php|<\? |<\?=|<\s*script\s*language\s*=\s*"\s*php\s*"\s*>|<%[^%]*%>).*?(\?>|<\s*/\s*script\s*>|%>)@sm', '', $html);

		return $html;
	}

	private function minifyHtml($html) {
		//html comments but keep ie conditionals
		$html = preg_replace('/<!--(?!\s*\[if\s)(?!@@_KEEP_COMMENT_@@)(.*?)-->/sm', '', $html);
		$html = str_replace('<!--@@_KEEP_COMMENT_@@', '<!--', $html);

		//repeating spaces
		$html = preg_replace('/\s+/', ' ', $html);

		//repeating end lines
		$html = preg_replace('/\n+/', "\n", $html);
		$html = preg_replace('@> </@', '></', $html);
		/*		
		// space between tags
		$html = preg_replace('/> </', '><', $html);
		$html = preg_replace('/> </', '><', $html);*/
		/*
		$html = preg_replace('/ </', '<', $html);
		$html = preg_replace('/> /', '>', $html);
		*/
		$html = preg_replace('/ "/', '"', $html);
		$html = preg_replace('/" /', '"', $html);

		//repeating spaces
		$html = preg_replace('/\s+/', ' ', $html);

		return $html;
	}

	private function cleanHtml(&$html) {
		$self = $this;

		$html = preg_replace_callback('/@@__VTPL__ATTRIBUTE_PLACEHOLDER__(\d+)@@/',
					  function ($matches) use ($self) {
					  	return $self->attributes[$matches[1]];
					  }, $html); //sad hack :(

		$html = preg_replace_callback('/__VTPL__NEW_ATTRIBUTE_PLACEHOLDER__(\d+)=""/',
					  function ($matches) use ($self) {
					  	return $self->newAttributes[$matches[1]];
					  }, $html); //sad hack :(

		$html = preg_replace_callback('/<script holder="@@__VTPL__SCRIPT_PLACEHOLDER__(\d+)@@".*?><\/script>/',
					  function ($matches) use ($self) {
					  	if (VTPL_JS_MINIFY) {
					  		$script = $self->minifyJs($self->_scripts[$matches[1]]);
					  	} else {
					  		$script = $self->_scripts[$matches[1]];
					  	}

					  	return $script;
					  }, $html);

		//cleanup modified scripts
		$html = preg_replace('/<script holder="@@__VTPL__SCRIPT_PLACEHOLDER__(\d+)@@"[^>]*>/','', $html);

		/*
		Constants, replace @@_CONSTANT_NAME_@@ with the value defined for CONSTANT_NAME = 'value'
		*/
		$html = preg_replace_callback('/@@_CONSTANT_([A-Z_]*)_@@/',
					  function ($matches) use ($self) {
					  	return $self->constants[$matches[0]];
					  }, $html);

		/*
		 Moustache variables used in javascript code in html template, it replaces {variable} with $variable, if $variable is array then the output is json
		 */
		$html = preg_replace_callback('/{\s*(\$[\w\-\>\.]+)\|?([\w\.]+)?\s*}/',
					  function ($matches) use ($self) {
					  	$modifier = false;

					  	if (isset($matches[2])) {
					  		$modifier = $matches[2];
					  	}
					  	$variable = Vvveb\dotToArrayKey($matches[1]);
					  	$template =
						"<?php if (isset($variable)) {
                                if (is_array($variable)) {
                                    if ('$modifier') {
                                        \$modified = $modifier($variable);
                                        echo json_encode(\$modified);
                                    } else {
                                        echo json_encode($variable);
                                    }
                                } else {
                                    echo $variable;
                                }
                            }
                        ?>";

					  	return $template;
					  }, $html);

		$html = str_replace(['<_script language="php"><![CDATA[', ']]></_script>', '<_script language="php">', '</_script>'], ['<?php ', ' ?>', '<?php ', ' ?>'], $html);

		//remove data-v- attributes
		//$html = preg_replace('/data-v-[\-\w]+\s*=\s*"[^"]*"|data-v-[\-\w]+/','', $html);
	}

	function saveCompiledTemplate($compiledFile) {
		$this->processTemplateFile();
		$this->processTemplate();
		$this->setMultiLanguageText($this->document);

		if ($this->selector) {
			//extract only the specified part
			$elements      = $this->xpath->query($this->cssToXpath($this->selector));
			$componentNode = $elements->item($this->componentId);

			if ($componentNode) {
				$tmpDom = new DOMDocument();
				$tmpDom->appendChild($tmpDom->importNode($componentNode, true));

				if ($this->isHTML) {
					$html = $tmpDom->saveHTML();
				} else {
					$html = $tmpDom->saveXML();
				}

				$html = trim($html);
			} else {
				if ($this->isHTML) {
					$html = $this->document->saveHTML();
				} else {
					$html = $this->document->saveXML();
				}
			}
		} else {
			if ($this->isHTML) {
				$html = $this->document->saveHTML();
			} else {
				$html = $this->document->saveXML();
			}
		}

		$this->cleanHtml($html);

		$this->debug->log('SAVE', $compiledFile);

		//show debug console if needed
		if ($this->debug->enabled()) {
			$this->debug->printLog();
		}

		if (empty($html)) {
			Vvveb\log_error("compiled template is empty for $compiledFile");

			return false;
		}

		if ($this->checkSyntax) {
			file_put_contents($compiledFile, $html);

			try {
				token_get_all($html, TOKEN_PARSE);
			} catch (\ParseError $e) {
				//return \Vvveb\System\Core\exceptionHandler($e);
				$data = \Vvveb\System\Core\exceptionToArray($e, $compiledFile);

				return \Vvveb\System\Core\FrontController :: notFound(false, $data, 500);
			}
		} else {
			file_put_contents($compiledFile, $html);
		}

		return true;
	}
}
