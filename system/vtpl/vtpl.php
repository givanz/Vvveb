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

defined('VTPL_HTML_MINIFY') || define('VTPL_HTML_MINIFY', false);
define('VTPL_JS_MINIFY', false);
define('VTPL_PHP_MINIFY', false);
define('VTPL_CLEAN_COMP_OPT', false);

define('VTPL_DEBUG_SHOW_XPATH', true);
define('VTPL_DONT_ALLOW_PHP', true); //don't set to false unless ABSOLUTELY NECESSARY!

// PHP placeholder constants - used to embed PHP inside the DOM without triggering parsing.
// These are replaced with real php tags in cleanHtml().
define('VTPL_PHP_CDATA_OPEN', '<_script language="php"><![CDATA[');
define('VTPL_PHP_CDATA_CLOSE', ']]></_script>');
define('VTPL_PHP_OPEN', '<_script language="php">');
define('VTPL_PHP_CLOSE', '</_script>');

include 'macros.php';

include 'debug.php';

/**
 * Vtpl template engine — compiles `.tpl` selector commands against plain-HTML documents.
 *
 * See each property declaration below for individual types.
 */
#[\AllowDynamicProperties]
class Vtpl {
	/** @var string */
	private string $template = '';

	/** @var array<array-key, mixed> */
	private array $templatePath = [];

	/** @var string */
	private string $htmlPath = '';

	/** @var string */
	private string $relativePath = '';

	/** @var DOMDocument */
	private Dom\HTMLDocument|DOMDocument $document;

	/** @var bool */
	private bool $legacy = true;

	/** @var ?DOMXPath */
	private ?DOMXPath $xpath = null;

	/** @var string */
	private string $extension = 'tpl';

	/** @var string */
	private string $translationFunction = '\Vvveb\__';

	/** @var bool */
	private bool $removeComments = true;

	/** @var bool */
	private bool $removeVattrs = false;

	/** @var bool */
	private bool $minifyHtml = VTPL_HTML_MINIFY;

	/** @var string */
	private string $documentType = 'html';

	/** @var bool */
	private bool $checkSyntax = true;

	/** @var ?string */
	private ?string $selector = null;

	/** @var array<array-key, mixed>|null */
	private ?array $selectors = null;

	/** @var array<array-key, mixed>|null */
	private ?array $replaceConstants = null;

	/** @var ?int */
	private ?int $componentId = null;

	/** @var ?string */
	private ?string $componentContent = null;

	/** @var array<array-key, mixed>|null */
	private ?array $froms = null;

	/** @var array<array-key, mixed> */
	private array $constants = [];

	/** @var array<array-key, mixed> */
	private array $variables = [];

	/** @var array<array-key, mixed> */
	private array $strings = [];

	/** @var array<array-key, mixed> */
	private array $scripts = [];

	/** @var VtplDebug */
	private VtplDebug $debug;

	/** @var array<array-key, mixed> */
	private array $phpCode = [];

	/** @var array<array-key, mixed> */
	private array $modifiers = ['outerHTML', 'text', 'before', 'after', 'append', 'prepend', 'deleteAllButFirst', 'deleteAllButFirstChild', 'delete', 'if_exists', 'hide', 'addClass', 'removeClass'];

	/** @var array<array-key, mixed> */
	private array $variableFilters =
		[
			'capitalize'        => 'ucfirst($$0)',
			'html_to_text'      => 'Vvveb\htmlToText($$0)',
			'cdata'             => 'CDATA_START . ( $$0 ). CDATA_END',
			'friendly_date'     => 'Vvveb\friendlyDate($$0)',
			'friendly_number'   => 'Vvveb\parseQuantity($$0)',
			'human_readable'    => 'Vvveb\humanReadable($$0)',
			'date'              => 'date($$1,is_int($$0) ? $$0 : strtotime($$0))',
			'truncate'          => 'substr($$0, 0, $$1)',
			'truncate_words'    => 'Vvveb\truncateWords($$0,$$1)',
			'replace'           => 'str_replace($$1, $$2, $$0)',
			'uppercase'         => 'strtoupper($$0)',
			'lowercase'         => 'strtolower($$0)',
			'append'            => '$$1 . $$0',
			'prepend'           => '$$0 . $$1',
			'strip_html'        => 'strip_tags($$0)',
			'strip_newlines'    => 'str_replace("\n",\' \', $$0)',
			'mod'               => ['tag', 'if (@++$_modc_@@__VTPL_rand()__@@ % (int)$$1 === (int)$$2) {', 'if (@++$_modc_@@__VTPL_rand()__@@ % (int)$$1 === (int)$$2) {'],
			'mod_class'         => ['class', '<?php if (@++$_modc_@@__VTPL_rand()__@@ % (int)$$1 === (int)$$2) echo $$0;?>'],
			'conditional_class' => ['class', '<?php if (@++$_modc_@@__VTPL_rand()__@@ % (int)$$1 === (int)$$2) echo $$0;?>'],
			'iteration_class'   => ['class', '<?php if (@++$_iterc_@@__VTPL_rand()__@@ === (int)$$2) echo $$1;?>'],
			'number_format'     => 'number_format($$0, $$1, $$2, $$3)',
			'only_decimals'     => 'substr($$0, (($_strpos = strrpos($$0, \'.\')) !== false)?$_strpos + 1:-100, ($_strpos !== false)?10:false)',
			'without_decimals'  => 'substr($$0, 0, strrpos($$0, \'.\'))',
		];

	/** @var int */
	private int $attributesIndex = 0;

	/** @var int */
	private int $newAttributesIndex = 0;

	/** @var array<array-key, mixed> */
	private array $attributes = [];

	/** @var array<array-key, mixed> */
	private array $newAttributes = [];

	/** @var bool */
	private bool $externalElements = false;

	/** @var bool */
	public bool $escapeInline = false;

	/** @var array<array-key, mixed> */
	private array $prefix = [];

	/**
	 * @param ?string $selector
	 * @param ?int    $componentId
	 * @param ?string $componentContent
	 */
	public function __construct(?string $selector = null, ?int $componentId = null, ?string $componentContent = null) {
		$this->templatePath = [];

		$this->debug = new VtplDebug();

		if (VTPL_DEBUG) {
			$this->debug->enable(true);
		}

		$this->selector         = $selector;
		$this->componentId      = $componentId;
		$this->componentContent = $componentContent;

		//libxml_disable_entity_loader();

		$this->document = new DOMDocument();
		$this->legacy   = true;

		$this->configureDomDocument($this->document);
	}

	/**
	 * Apply standard configuration options to a DOMDocument instance.
	 *
	 * @param DOMDocument $doc
	 *
	 * @return void
	 */
	private function configureDomDocument(Dom\HTMLDocument|DOMDocument $doc): void {
		if ($this->legacy) {
			$doc->preserveWhiteSpace  = true;
			$doc->recover             = true;
			$doc->strictErrorChecking = false;
			$doc->substituteEntities  = false;
			$doc->formatOutput        = false;
			$doc->resolveExternals    = false;
			$doc->validateOnParse     = false;
			$doc->xmlStandalone       = true;
		}
	}

	/**
	 * @param bool $flag
	 *
	 * @return void
	 */
	public function removeVattrs(bool $flag = true): void {
		$this->removeVattrs = $flag;
	}

	/**
	 * @param bool $flag
	 *
	 * @return void
	 */
	public function setMinifyHtml(bool $flag = true): void {
		$this->minifyHtml = $flag;
	}

	/**
	 * @return string
	 */
	public function getDocumentType(): string {
		return $this->documentType;
	}

	/**
	 * @return Dom\Document|DOMDocument
	 */
	public function getDocument(): Dom\Document|DOMDocument {
		return $this->document;
	}

	/**
	 * @param ?string      $selector
	 * @param false|string $command
	 *
	 * @return void
	 */
	public function addCommand(?string $selector, false|string $command = false): void {
		if ($selector) {
			$this->template .= "\n $selector";

			if ($command) {
				//if not php code then quote string
				if (!str_starts_with($command, '<?')) {
					$command = "'$command'";
				}

				$this->template .= " = $command\n";
			} else {
				$this->template .= "\n";
			}
		}
	}

	/**
	 * @param string $path
	 *
	 * @return void
	 */
	public function setHtmlPath(string $path): void {
		$this->htmlPath = $path;
	}

	/**
	 * @param string $path
	 *
	 * @return void
	 */
	public function setRelativePath(string $path): void {
		$this->relativePath = $path;
	}

	/**
	 * @param string $path
	 *
	 * @return void
	 */
	public function addTemplatePath(string $path): void {
		if ($path) {
			$this->templatePath[] = $path;
		}
	}

	/**
	 * @param string $templateFile
	 * @param bool   $before
	 *
	 * @return void
	 */
	public function loadTemplateFile(string $templateFile, bool $before = false): void {
		if (file_exists($templateFile)) {
			if ($before) {
				$this->template  = file_get_contents($templateFile) . "\n\n" . $this->template;
			} else {
				$this->template .= "\n\n" . file_get_contents($templateFile);
			}
		}
	}

	/**
	 * @param string       $templateFile
	 * @param false|string $extra
	 *
	 * @return ?bool
	 */
	public function loadTemplateFileFromPath(string $templateFile, false|string $extra = false): ?bool {
		$path = '';

		foreach ($this->templatePath as $path) {
			$this->debug->log('LOAD', $path . $templateFile);

			if (!file_exists($path . $templateFile)) {
				$this->debug->log('LOAD', '<b>!EXISTS</b>' . $path . $templateFile);

				continue;
			}

			//load from the first matching path only (paths are priority ordered,
			//same semantics as the import loader); loading from every path would
			//duplicate content when a theme overrides a base template
			$this->template .= file_get_contents($path . $templateFile);

			break;
		}

		if ($extra) {
			$this->template .= $extra;
		}

		if (!$this->template) {
			$this->debug->log('LOAD', '<b>EMPTY</b>' . $path . $templateFile);

			return false;
		}

		return true;
	}

	/**
	 * @return void
	 */
	private function processPhpcode(): void {
		// Placeholders, replace variables, php code etc with placeholders.
		//$this->template = preg_replace('@\/\/[^\n\r]+?(?:\*\)|[\n\r])@','', $this->template);
		preg_match_all('/(?<!["\'])<\?php(.*?)\?>/s', $this->template, $phpCode);

		$patternsPhp     = [];
		$placeholdersPhp = [];

		for ($i = 0; $i < count($phpCode[1]); $i++) {
			$patternsPhp[]     = '/' . preg_quote($phpCode[0][$i], '/') . '/';
			$placeholdersPhp[] = "replace_php_code-{$i}";
			// double backslashes must be escaped if we want to use them in the replacement argument
			$phpCode[0][$i] = str_replace('\\\\', '\\\\\\\\', $phpCode[1][$i]);
		}

		if ($placeholdersPhp) {
			$this->template = preg_replace($patternsPhp, $placeholdersPhp, $this->template);
		}

		$this->phpCode   = $phpCode[0];
	}

	/**
	 * @return void
	 */
	private function processFroms(): void {
		// Froms - from(index.html|#element)
		preg_match_all('/from\(([^\|]+)\|(.+)\)/', $this->template, $froms);
		$patternsFroms     = [];
		$placeholdersFroms = [];

		for ($i = 0; $i < count($froms[1]); $i++) {
			$patternsFroms[]     = '/' . preg_quote($froms[0][$i], '/') . '/';
			$placeholdersFroms[] = "replace_from-{$i}\n";
			// double backslashes must be escaped if we want to use them in the replacement argument
			$froms[0][$i] = str_replace('\\\\', '\\\\\\\\', $froms[1][$i]);
		}

		if ($froms[0]) {
			$this->template = preg_replace($patternsFroms, $placeholdersFroms, $this->template);
		}

		$this->froms     = $froms;
	}

	/**
	 * @return void
	 */
	private function processStrings(): void {
		// strings
		//single quote
		preg_match_all("/=\\s+'[^'\\\r\n]*(?:\\.[^'\\\r\n]*)*'(?!\\])/s", $this->template, $stringsSingle);
		//doube quote
		preg_match_all('/=\s+"[^"\\\r\n]*(?:\.[^"\\\r\n]*)*"(?!\])/s', $this->template, $strings);

		$strings             = array_values(array_unique($strings[0]));
		$stringsSingle       = array_values(array_unique($stringsSingle[0]));
		$strings             = array_merge((array)$strings, (array)$stringsSingle);
		$patternsStrings     = [];
		$placeholdersStrings = [];

		for ($i = 0; $i < count($strings); $i++) {
			$string                = trim($strings[$i], '= ');
			$patternsStrings[]     = '/' . preg_quote($string, '/') . '/';
			$placeholdersStrings[] = "replace_string-{$i}";
			// double backslashes must be escaped if we want to use them in the replacement argument
			$strings[$i] = str_replace('\\\\', '\\\\\\\\', $string);
		}

		if ($strings) {
			$this->template = preg_replace($patternsStrings, $placeholdersStrings, $this->template);
		}

		$this->strings   = $strings;
	}

	/**
	 * @return void
	 */
	private function processVariables(): void {
		preg_match_all('/(?<!["\'\[])(\$.+)/', $this->template, $variables);

		$patternsVariables     = [];
		$placeholdersVariables = [];
		$variables[1]          = array_unique($variables[1]);
		usort($variables[1], fn ($a, $b) => strlen($b) <=> strlen($a));

		for ($i = 0; $i < count($variables[1]); $i++) {
			$patternsVariables[]     = '/' . preg_quote($variables[1][$i], '/') . '/';
			$placeholdersVariables[] = "replace_variable-{$i}";
			// double backslashes must be escaped if we want to use them in the replacement argument
		}

		$this->template = preg_replace($patternsVariables, $placeholdersVariables, $this->template);

		$this->variables = $variables[1];
	}

	/**
	 * Process the raw template commands: expand imports, extract php/variable/
	 * from/string placeholders into lookup tables, then parse selector lines.
	 */
	private function processTemplateFile(): void {
		if (!$this->template) {
			return;
		}

		$this->expandImports();
		$this->processPhpcode();
		$this->processVariables();
		$this->processFroms();
		$this->processStrings();

		$this->parseSelectorLines();
	}

	/**
	 * Expand import(file.tpl) directives by inlining the referenced files,
	 * repeating until no imports remain (imports may nest).
	 */
	private function expandImports(): void {
		$foundImports = true;
		//expand imports
		while ($foundImports) {
			$foundImports = preg_match_all("/import\\(([^\\&%'`\\@{}~!#\\(\\)&\\^\\+,=\\[\\]]*?\\.{$this->extension})\\,?(.+)?\\);?/", $this->template, $imports);

			for ($i = 0; $i < count($imports[0]); $i++) {
				$content       = '';
				$importContent = '';

				foreach ($this->templatePath as $path) {
					$import = $imports[1][$i];

					if (str_starts_with($import, '/plugins/')) {
						$importFile = DIR_PLUGINS . substr($import, 9);
					} else {
						if ($import[0] === '/') {
							$importFile = DIR_ROOT . $import;
						} else {
							$importFile = $path . $import;
						}
					}

					if (str_contains($importFile, '*')) {
						$files = glob($importFile);
					} else {
						$files = [$importFile];
					}

					$found = false;

					foreach ($files as $importFile) {
						if (file_exists($importFile)) {
							$parameter = trim($imports[2][$i]);
							$this->debug->log('LOAD', $importFile);

							if (!empty($parameter)) {
								//if php array then parameter is a template variables list
								if ($parameter[0] === '{'/* || $parameter[0] == '['*/) {
									$importContent = file_get_contents($importFile);

									//process template
									$templateParams = json_decode($parameter, true);

									foreach ($templateParams as $name => $value) {
										$importContent = str_replace('{{' . $name . '}}', $value, $importContent);
									}
								} else {
									//parameter is a css selector to check if the element exists and conditionally include template
									$elements = $this->query($parameter);
									if ($elements && $elements->length) {
										//found, load template below
										$importContent = file_get_contents($importFile);
									} else {
										//not found, replace import with nothing
										$this->template = str_replace($imports[0][$i], '', $this->template);

										continue;
									}
								}
							} else {
								$importContent = file_get_contents($importFile);
							}

							//remove comments
							$importContent = preg_replace("/(?<![\"'])\\/\\*.*?\\*\\/|\\s*(?<![\"'])\\/\\/[^\n]*/s", '', $importContent);
							$content .= $importContent . "\n";

							$found = true;
						} else {
							$this->debug->log('VTPL_IMPORT_FILE_NOT_EXIST', $importFile);
						}
					}

					if ($found) {
						break;
					}
				}

				$this->template = str_replace($imports[0][$i], $content, $this->template);
			}
		}
	}

	/**
	 * Normalize template whitespace/comments and split the remaining command
	 * text into selector lines ("selector = value" pairs or bare selectors).
	 */
	private function parseSelectorLines(): void {
		//remove comments
		$this->template = preg_replace("/(?<![\"'])\\/\\*.*?\\*\\/|\\s*(?<![\"'])\\/\\/[^\n]*/s", '', $this->template);
		$this->template = preg_replace('/\n+/', "\n", $this->template);
		$this->template = preg_replace('/(?<=\=)\s*\n/', '', $this->template);

		$this->template = str_replace("\n\n", "\n", trim($this->template));
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
	 * @param string $selector The CSS-selector
	 *
	 * @return string
	 */
	public function cssToXpath(string $selector): string {
		$selector = (string)$selector;

		//protect quoted literals so later rules (brackets/class/id) can't mangle
		//attribute values like name="settings[frontend]" — placeholders carry no
		//special css characters and are restored after the rule table runs
		$quotedLiterals = [];
		$selector       = preg_replace_callback('/"[^"]*"/', function(array $m) use (&$quotedLiterals): string {
			$key                  = '__QT' . count($quotedLiterals) . '__';
			$quotedLiterals[$key] = substr($m[0], 1, -1);

			return '"' . $key . '"';
		}, $selector);

		//convert , to | union operator to allow multiple queries
		$selector = str_replace(',', '|', $selector);

		$cssSelector = [
			// E > F: Matches any F element that is a child of an element E
			'/\s*>\s*/',	// /

			// E + F: Matches any F element immediately preceded by an element
			'/\s+\+\s+/',	// /following-sibling::*[1]/self::

			// E F: Matches any F element that is a descendant of an E element
			'/([\w\*="\[\]#._-])\s+([\w\*="\[\]#._-])/', 	// \1//\2'  //'/([a-zA-Z\*="\[\]#._-])\s+([a-zA-Z\*#._-])/',

			// E:first-child: Matches element E when E is the first child of its parent
			'/([a-z#\.]\w*):first-child/',	// *[1]/self::\1

			// E:nth-child() Matches the nth child element
			'/([a-z#\.]\w*):nth-child\((\d+)\)/',	// *[\2]/self::\1

			// E:first: Matches the first element from the set
			'/([a-z#\.]\w*):first/',	// \1[1]

			// E:nth(2): Matches the nth element from the set
			'/([a-z#\.]\w*):nth\((\d+)\)/',		// \1[\2]

			// E[foo=warning]: Matches any E element whose unquoted "foo" attribute value equals "warning"
			// bracketed value first (name=settings[enabled] — invalid css but common in templates),
			// so the plain unquoted rule can't stop at the inner ']'
			'/([a-z]\w*|\*)\[([a-z][\w\-_]*)\=([\w\-\.]+)\[([\w\-_]+)\]\]/',	// \1[ contains( concat( " ", @\2, " " ), concat( " ", "\3[\4]", " " ) ) ]
			'/\[([a-z][\w\-_]*)\=([\w\-\.]+)\[([\w\-_]+)\]\]/',	//	*[ contains( concat( " ", @\1, " " ), concat( " ", "\2[\3]", " " ) ) ]
			'/([a-z]\w*|\*)\[([a-z][\w\-_]*)\=([^\s\]"]+)\]/',	// \1[ contains( concat( " ", @\2, " " ), concat( " ", "\3", " " ) ) ]

			// E[foo="warning"]: Matches any E element whose "foo" attribute value is exactly equal to "warning"
			'/([a-z]\w*)\[([a-z][\w\-_]*)\="([^"]*)"\]/',	// \1[ contains( concat( " ", @\2, " " ), concat( " ", "\3", " " ) ) ]

			// E[foo]: Matches any E element with the "foo" attribute set (whatever the value)
			'/(?<![\w"])([a-z]\w*)\[([a-z][\w_\-]*)\]/',	// \1 [ @\2 ]'

			// E[!foo]: Matches any E element without the "foo" attribute set
			'/([a-z]\w*)\[!([a-z][\w\-_]*)\]/',		//	\1 [ not(@\2) ]

			// [foo=warning]: Matches any element whose unquoted "foo" attribute value equals "warning"
			'/\[([a-z][\w\-_]*)\=([^\s\]"]+)\]/',	//	*[ contains( concat( " ", @\1, " " ), concat( " ", "\2", " " ) ) ]

			// [foo="warning"]: Matches any element whose "foo" attribute value is exactly equal to "warning"
			'/\[([a-z][\w\-_]*)\=\"(.*)\"\]/',	//	*[@\1 = "\2"]

			// element[foo*="warning"]: Matches any element whose "foo" attribute value contains the string "warning"
			'/([a-z]\w*|\*)\[([a-z][\w\-_]*)\*\=\"([^"]+)\"\]/',	// \1[ contains( @\2, "\3" ) ]

			// [foo*="warning"]: Matches any element whose "foo" attribute value contains the string "warning" and has other attributes
			'/(?<=\])\[([a-z][\w\-_]*)\*\=\"([^"]+)\"\]/',		//	[contains(@\1,"\2")]

			// [foo*="warning"]: Matches any element whose "foo" attribute value contains the string "warning"
			'/\[([a-z][\w\-_]*)\*\=\"([^"]+)\"\]/',		//	[ contains( @\1, "\2" ) ]

			// [foo^="warning"]: Matches any element whose "foo" attribute value begins with the string "warning"
			'/\[([a-z][\w_\-]*)\^\=\"([^"]+)\"\]/',		//	[starts-with(@\1,"\2")]

			// [foo$="warning"]: Matches any element whose "foo" attribute value ends  with the string "warning"
			'/\[([a-z][\w_\-]*)\$\=\"([^"]+)\"\]/',		// [ends-with(@\1,"\2")]

			// [foo][baz]: Matches any element with the "foo" attribute set (whatever the value)
			'/(?<=\])(?<! )\[([a-z][\w_\-]*)\]/',		//	[ @\1 ]

			// [foo]: Matches any element with the "foo" attribute set (whatever the value)
			'/(?<![\w"])\[([a-z][\w_\-]*)\]/',	//	*[ @\1 ]

			// [!foo]: Matches any element without the "foo" attribute set (whatever the value)
			'/\[!([a-z][\w_\-]*)\]/',	//	*[ not(@\1) ]

			// element[foo*]: Matches any element that starts with "foo" attribute (whatever the value)
			'/(\w+)\[([a-z][\w\-]*)\*\]/',	//	\1 [ @*[starts-with(name(), "\2")] ]

			// [foo*]: Matches any element that starts with "foo" attribute (whatever the value) and has other attributes
			'/(?<=\])\[([a-z][\w\-]*)\*\]/',	//	[ @*[starts-with(name(), "\1")] ]

			// [foo*]: Matches any element that starts with "foo" attribute (whatever the value) and is a single attribute
			'/(?<!\])\[([a-z][\w\-]*)\*\]/',	//	*[ @*[starts-with(name(), "\1")] ]

			// div.warn*: HTML only. The same as DIV[class*="warning"]
			'/([a-z]\w*|\*)\.([a-z][\w\-_]*)\*/',	//	\1[ contains( concat( " ", @class, " " ), concat( " ", "\2") ) ]

			// div.warning: HTML only. The same as DIV[class~="warning"]
			'/([a-z]\w*|\*)\.([a-z][\w\-_]*)+/',	//	\1[ contains( concat( " ", @class, " " ), concat( " ", "\2", " " ) ) ]

			// .warn*: HTML only. The same as [class*="warning"]
			'/\.([a-z][\w\-\_]*)\*/',	//	*[ contains( concat( " ", @class, " " ), concat( " ", "\1") ) ]

			// .warning: HTML only. The same as [class~="warning"]  and has other attributes
			'/(?<=\])\.([a-z][\w\-\_]*)+/',	//	*[ contains( concat( " ", @class, " " ), concat( " ", "\1", " " ) ) ]

			// .warning: HTML only. The same as [class~="warning"]
			'/\.([a-z][\w\-\_]*)+/',	//	*[ contains( concat( " ", @class, " " ), concat( " ", "\1", " " ) ) ]

			// E#myid: Matches any E element with id-attribute equal to "myid"
			'/([a-z]\w*)\#([a-z][\w\-_]*)/',	//	\1[ @id = "\2" ]

			// #myid: Matches any E element with id-attribute equal to "myid"
			'/\#([a-z][\w\-_]*)/',		//	*[ @id = "\1" ]

			// negation pseudo selector
			'/\:not\(([^\)]+?)\)/', //	:not(\1)

			// empty pseudo selector
			'/\:empty/', //:empty
		];

		$xpathQuery = [
			'/', //element > child
			'/following-sibling::*[1]/self::', // element + precedent
			'\1//\2', //element descendent
			'*[1]/self::\1', //element:first-child
			'*[\2]/self::\1', //element:nth-child(2)
			'\1[1]', //element:first
			'\1[\2]', //element:nth(2)
			'\1[ contains( concat( " ", @\2, " " ), concat( " ", "\3[\4]", " " ) ) ]', //element[attribute=bwarningacketed unquoted]
			'*[ contains( concat( " ", @\1, " " ), concat( " ", "\2[\3]", " " ) ) ]', //[attribute=bracketed unquoted]
			'\1[ contains( concat( " ", @\2, " " ), concat( " ", "\3", " " ) ) ]', //element[attribute=string unquoted]
			'\1[ contains( concat( " ", @\2, " " ), concat( " ", "\3", " " ) ) ]', //element[attribute="string"]
			'\1 [ @\2 ]', //element[attribute]
			'\1 [ not(@\2) ]', //element[!attribute]
			'*[ contains( concat( " ", @\1, " " ), concat( " ", "\2", " " ) ) ]', //[foo=warning unquoted]
			'*[@\1 = "\2"]', //[foo="warning"]
			'\1[ contains( @\2, "\3" ) ]', //element[foo*="warning"]
			'[contains(@\1,"\2")]', //[foo*="warning"]
			'[ contains( @\1, "\2" ) ]', //[foo*="warning"]
			'[starts-with(@\1,"\2")]', //[foo^="warning"]
			'[ends-with(@\1,"\2")]', //[foo$="warning"]
			'[ @\1 ]', //[attribute][attribute]
			'*[ @\1 ]', //[attribute]
			'[ not(@\1) ]', //[!attribute]
			'\1 [ @*[starts-with(name(), "\2")] ]', //element[attr*]
			'[ @*[starts-with(name(), "\1")] ]', //[attr*] - attribute with other attributes
			'*[ @*[starts-with(name(), "\1")] ]', //[attr*] - single attribute
			'\1[ contains( concat( " ", @class, " " ), concat( " ", "\2") ) ]', //element[class*="string"]
			'\1[ contains( concat( " ", @class, " " ), concat( " ", "\2", " " ) ) ]', //element[class~="string"]
			'*[ contains( concat( " ", @class, " " ), concat( " ", "\1") ) ]', //[class*="string"]
			'[ contains( concat( " ", @class, " " ), concat( " ", "\1", " " ) ) ]', //element[class~="string"]
			'*[ contains( concat( " ", @class, " " ), concat( " ", "\1", " " ) ) ]', //element[class~="string"]
			'\1[ @id = "\2" ]', //element#id
			'*[ @id = "\1" ]', //#id
			//'(\1 except \2)', //negation pseudo selector
			'[not(self::\1)]', //negation pseudo selector
			'[not(text())]', //empty pseudo selector
		];

		$result = (string)'//' . preg_replace($cssSelector, $xpathQuery, $selector);
		$result = strtr($result, $quotedLiterals);
		$this->debug->log('CSS_XPATH_TRANSFORM', $result);

		return $result;
	}

	/**
	 * @param string $selector
	 *
	 * @return Dom\NodeList<\Dom\Node>|DOMNodeList<\DOMNode>|false
	 */
	public function query(string $selector): Dom\NodeList|DOMNodeList|false {
		if ($this->legacy) {
			$xpath    = $this->cssToXpath($selector);
			$result   = $this->xpath->query($xpath);
		} else {
			//missing css support for
			//[data-v-menu-item-*] => [attribute-name^="data-v-menu-item-"]
			$result = $this->document->querySelectorAll($selector);
		}

		return $result;
	}

	/**
	 * @param string                                                   $modifier
	 * @param Dom\NodeList<\Dom\Node>|DOMNodeList<\DOMNode>|false|null $elements
	 * @param array<array-key, mixed>|string                           $val
	 *
	 * @return void
	 */
	private function processElements(string $modifier, null|Dom\NodeList|DOMNodeList|false $elements, array|string $val): void {
		//no match (or invalid xpath expression) - nothing to modify
		if (!$elements || !$elements->length) {
			return;
		}

		switch ($modifier) {
			case 'deleteAllButFirstChild':
				$this->deleteAllButFirstChild($elements);

				break;

			case 'deleteAllButFirst':
				$this->deleteAllButFirst($elements);

				break;

			case 'outerHTML':
				$this->outerHTML($elements, $val);

				break;

			case 'text':
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

			case 'removeAttribute':
				$this->removeAttribute($elements, $val);

				break;

			case '':
				$this->innerHTML($elements, $val);

				break;

			default:
				$this->setAttribute($elements, $modifier, $val);
		}
	}

	/**
	 * @return void
	 */
	/**
	 * Expands comma-separated selector lines ("a, b|mod = value") into one
	 * selector entry per selector, preserving the modifier and value.
	 */
	private function expandCommaSelectors(): void {
		$count = count($this->selectors);
		for ($i = 0; $i < $count; $i++) {
			$data = $this->selectors[$i];
			//$data[0] = selector
			if (str_contains($data[0], ',')) {
				$selectorComponents       = explode('|', $data[0]);
				$selector                 = $selectorComponents[0];
				$modifier                 = (isset($selectorComponents[1])) ? trim($selectorComponents[1]) : '';

				$selectors    = explode(',', $selector);
				$newSelectors = [];
				foreach ($selectors as $selector) {
					$newSelectors[] = [trim($selector) . ($modifier ? '|' . $modifier : ''), $data[1] ?? ''];
				}

				$newCount = count($newSelectors) - 1;

				array_splice($this->selectors, $i, 1, $newSelectors);
				$count += $newCount;
				$i += $newCount;
			}
		}
	}

	private function processTemplate(): void {
		if (isset($this->selectors)) {
			//check for multiple selectors

			$this->expandCommaSelectors();

			foreach ($this->selectors as &$data) {
				$selector                 = $data[0];
				$selectorComponents       = explode('|', $selector);
				$selector                 = $selectorComponents[0];
				$modifier                 = (isset($selectorComponents[1])) ? trim($selectorComponents[1]) : '';
				$value                    = (isset($data[1])) ? $data[1] : '';
				$this->externalElements   = false;

				//enable disable debugging
				if (!$selector) {
					continue;
				}
				$isConstant = false;

				if (str_contains($selector, '@@_CONSTANT_')) {
					$isConstant = true;
				}

				$prefix   = &$this->prefix;
				$isPrefix = false;

				/**
				get all set prefix and save values
				@my-prefix = #selector .class[attribute]
				 */
				$selector = preg_replace_callback(
					'/^@([a-zA-Z][a-zA-Z0-9_-]+)(?![a-zA-Z0-9_-])\s+=\s+(.+)$/',
					function($matches) use (&$prefix, &$isPrefix) {
						$name  = $matches[1];
						$value = $matches[2];

						$prefix[$name] = $value;
						$isPrefix      = true;

						return trim($matches[0]);
					},
					$selector
				);

				//if the line is only for set prefix then skip further processing
				if ($isPrefix) {
					continue;
				}
				//replace all prefix with actual values
				$selector = preg_replace_callback(
					'/@([a-zA-Z][a-zA-Z0-9_-]+)(?![a-zA-Z0-9_-])/',
					function($matches) use (&$prefix) {
						$name = $matches[1];

						if (isset($prefix[$name])) {
							return $prefix[$name];
						}

						return trim($matches[0]);
					},
					$selector
				);

				$this->debug->log('SELECTOR', $selector);

				$val = $value;

				if ($selector === 'debug') {
					$this->debug->enable(($value === 'true') ? true : false);
				} else {
					$valueElements = explode('-', $value);

					switch ($valueElements[0]) {
						case 'replace_string':
							$val   = $value;
							$index = (int)$valueElements[1];

							if (isset($this->strings[$index])) {
								$val = trim($this->strings[$index], '"\'');
								$this->debug->log('SELECTOR_STRING', $this->strings[$index]);
							} else {
								$this->debug->log('SELECTOR_STRING', "$index not found for $value");
							}

							break;

						case 'replace_php_code':
							$phpCode = $this->phpCode[(int)$valueElements[1]] ?? '';
							/*
							if (VTPL_PHP_MINIFY === true) {
								$phpCode = $this->minifyPhp($phpCode);
							}
							*/

							if ($modifier && !in_array($modifier, $this->modifiers)) {
								$val = '<_script language="php"><![CDATA[' . $this->minifyPhp($phpCode) . ']]></_script>';
							} else {
								if ($modifier === 'if_exists' || $modifier === 'hide') {
									//these modifiers take a boolean expression — strip a
									//statement wrapper (echo/print + trailing ;) so ifExists()
									//never wraps statements in isset()
									$expr = trim($phpCode);
									$expr = preg_replace('/^\s*(?:echo|print)\s+/s', '', $expr) ?? $expr;
									$expr = preg_replace('/\s*;\s*$/s', '', $expr) ?? $expr;
									$val  = str_contains($expr, ';') ? "({$phpCode})" : "({$expr})";
								} else {
									$val = VTPL_PHP_CDATA_OPEN . $this->minifyPhp($phpCode) . VTPL_PHP_CDATA_CLOSE;
								}
							}
							$this->debug->log('SELECTOR_PHP', $phpCode);

							break;

						case 'replace_variable':
							$variable = $this->variables[(int) $valueElements[1]] ?? '';
							if ($modifier) {
								if ($modifier === 'if_exists' || $modifier === 'hide') {
									$val = $variable;
								} elseif (! in_array($modifier, $this->modifiers) || $modifier === 'addClass') {
									$val = VTPL_PHP_CDATA_OPEN . 'if (isset(' . $variable . ')) echo htmlspecialchars(' . $variable . ');' . VTPL_PHP_CDATA_CLOSE;
								}
							} else {
								$echo = $isConstant ? 'echo ' . $variable : 'echo htmlspecialchars(' . $variable . ')';
								$val = VTPL_PHP_CDATA_OPEN . 'if (isset(' . $variable . ')) ' . $echo . ';' . VTPL_PHP_CDATA_CLOSE;
							}

							$this->debug->log('SELECTOR_VARIABLE', $variable);

							break;

						case 'replace_from':
							$this->externalElements   = true;
							$val                      = $valueElements;

							break;
					}

					if ($isConstant) {
						$this->constants[$selector] = $val;

						continue;
					}

					$elements      = $this->query($selector);

					if (!$elements) {
						$this->debug->log(0, ' [empty]');
					} else {
						if ($elements->length === 0) {
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

	/**
	 * Replace $$N placeholders in a filter command string with option values.
	 * Non-zero indices are quoted as string literals.
	 *
	 * @param string                  $command
	 * @param array<array-key, mixed> $options
	 *
	 * @return string
	 */
	private function replaceFilterVars(string $command, array &$options): string {
		return preg_replace_callback(
			'@\$\$(\d+)@',
			function($matches) use (&$options) {
				$index = (int)$matches[1];

				if ($index > 0) {
					$options[$index] = '\'' . trim($options[$index], '\'') . '\'';
				}

				return $options[$index];
			},
			$command
		);
	}

	/*
	 Process data-filter-* attributes defined in filters array.
	 Process attributes with json and transforms them to php arrays ex: data-my-data='{var:"value"}'
	 Process macro definitions like @@macro Mymacro('var1', 'var2') Mymacro must be a function defined with name vtplMymacro and must accept two variables like in definition
	 Process json path, for a node with data-v-myjson='{var:{subvar1:val1, subvar2:val2}}' @@myjson.var.subvar1@@ will return val1
	 */

	/**
	 * @param string                  $value
	 * @param Dom\HTMLElement|DOMNode &$node
	 *
	 * @return string
	 */
	private function processAttributeFilters(string $value, Dom\HTMLElement|DOMNode &$node): string {
		/*
		filters
		search for filters and their options
		@filter_([^ :$]+):?(\'[^\']+\'|[^ $]+)?@ old
		 */
		$filters = [];
		$length  = $node->attributes->length;

		for ($i = 0; $i < $length; $i++) {
			if ($item = $node->attributes->item($i)) {
				$name = $item->name;

				if (str_contains($name, 'data-filter')) {
					$name           = str_replace('data-filter-', '', $name);
					$filters[$name] = $item->value;
					//$node->removeAttribute($item->name);
				}
			}
		}

		//if ($class && preg_match_all('@filter_([^ :$]+)(:\'[^\']+\'|:[^ $]+)*@', $class, $matches, PREG_SET_ORDER) > 0)
		if ($filters) {
			$chain = '_$variable';

			foreach ($filters as $name => $options) {
				if ($options) {
					//string is json
					if ($options[0] === '{'/* || $options[0] == '['*/) {
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

					if (!$commands) {
						continue;
					}

					foreach ($commands as &$command) {
						$commandVariableCount = preg_match_all('@\$\$[1-9]+@', $command);

						//if different parameter number then don't add filter to filter chain
						if ($commandVariableCount != count($options)) {
							$this->debug->log('warning', 'Invalid number of options for filter <b>' . $name . '</b> for "' . $name . '"');

							continue 2;
						}

						//run php functions if any
						$command = preg_replace_callback(
							'/@@__VTPL_([^_]+)__@@/',
							fn ($matches) => eval('return ' . $matches[1] . ';'),
							$command
						);
					}

					if ($type === 'class') {
						$command = $this->replaceFilterVars($command, $options);
						$this->addNodeClass($node, trim($commands[1], '\''), false);
					} else {
						if ($type === 'tag') {
							$command  = $this->replaceFilterVars($command, $options);
							$openTag  = $this->replaceFilterVars($commands[1], $options);
							$closeTag = $this->replaceFilterVars($commands[2], $options);

							$nodeList = [$node]; //only one node and the methods accepts multiple nodes
							$this->tagWrap($nodeList, $openTag, $closeTag);
						} else {
							if (is_array($options)) {
								array_unshift($options, $chain);
							} else {
								$options[] = $chain;
							}

							$chain = $this->replaceFilterVars($commands[1], $options);
						}
					}
				} else {
					$this->debug->log('warning', 'Unknown filter <b>' . $name . '</b> for "' . $name . '"');
				}
			}

			preg_match('@echo htmlspecialchars\(([^)]+)\)@', $value, $variable);

			if ($variable) {
				$chain = str_replace('_$variable', $variable[1], $chain);
				$value = str_replace($variable[0], 'echo htmlspecialchars(' . $chain . ')', $value);
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

	/**
	 * Expand a JSON-looking value ({...}) to PHP array syntax via var_export.
	 *
	 * Shared by the @@__innerText__@@, @@__innerHTML__@@ and attribute
	 * placeholder expansions in processAttributeConstants().
	 *
	 * @param string|null $value
	 *
	 * @return string|null
	 */
	private function expandJsonValue(?string $value): ?string {
		if (isset($value[0]) && $value[0] === '{') {
			$value = var_export(json_decode($value, true), true);
		}

		return $value;
	}

	/**
	 * @param string                  $value
	 * @param Dom\HTMLElement|DOMNode $node
	 *
	 * @return string
	 */
	private function processAttributeConstants(string $value, Dom\HTMLElement|DOMNode $node): string {
		$value = preg_replace_callback(
			'/@@__innerText__@@/',
			function($matches) use ($node) {
				$value = $this->innerText([$node]);

				$value = $this->expandJsonValue($value);

				$this->debug->log('VTPL_ATTRIBUTE', '<b>VALUE </b>' . $value);

				return trim($value);
			},
			$value
		);

		$value = preg_replace_callback(
			'/@@__innerHTML__@@/',
			function($matches) use ($node) {
				$value = $this->innerHTML([$node]);

				$value = $this->expandJsonValue($value);

				$this->debug->log('VTPL_ATTRIBUTE', '<b>VALUE </b>' . $value);

				return $value;
			},
			$value
		);

		//attribute value
		$value = preg_replace_callback(
			'/@@__([\.a-zA-Z*_-]+)__@@/',
			function($matches) use ($node) {
				$attributeName = $matches[1];

				if (str_contains($attributeName, '*')) {
					//wildcard attribute
					$attributeName = str_replace('*', '', $attributeName);

					$value = '';

					foreach ($node->attributes as $attribute) {
						if (str_contains($attribute->name, $attributeName)) {
							$value = $attribute->value;
						}
					}
				} else {
					$value = $node->getAttribute($matches[1]);
				}

				$value = $this->expandJsonValue($value);

				$this->debug->log('VTPL_ATTRIBUTE', '<b>VALUE </b>' . $value);

				return $value;
			},
			$value
		);

		//run regex on attribute name @@__data-v-product-*:data-v-product-(*)__@@
		$value = preg_replace_callback(
			'/@@__([*a-zA-Z_-]+):([a-zA-Z-_\]\[\\\+\(\)\,\.\+\^:*]+)__@@/',
			function($matches) use ($node) {
				$attrib = $matches[1];
				$regex  = $matches[2];
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
			},
			$value
		);

		//attribute name ex @@__data-v-plugin-(.+)__@@
		$value = preg_replace_callback(
			'/@@__(.+?)__@@/',
			function($matches) use ($node) {
				$value = $node->getAttribute($matches[1]);
				$this->debug->log('VTPL_ATTRIBUTE', '<b>ATTRIB NAME</b> ' . $matches[1]);
				//expand shorthand expression (*) to regex ([a-zA-Z_0-9-]+)
				$regex = str_replace('(*)', '([a-zA-Z_0-9-\.]+)', $matches[1]);

				foreach ($node->attributes as $name => $attrNode) {
					if (preg_match("@$regex@", $name, $_match)) {
						//$value = \Vvveb\System\filter('@[0-9a-zA-Z_\-\.\#\/]+@', $_match[1], 500);
						$value = $_match[1] ?? null;
						$this->debug->log('VTPL_ATTRIBUTE', '<b>MATCH </b> ' . $value);
					} else {
						$this->debug->log('VTPL_ATTRIBUTE', '<b>NO MATCH </b>');
					}
				}

				return $value;
			},
			$value
		);

		$json = [];

		//check if attribute value is json string
		if ($node->hasAttributes()) {
			foreach ($node->attributes as $attr) {
				$name = str_replace('data-v-', '', $attr->nodeName);
				$val  = $attr->nodeValue;

				if ($val && ($val[0] === '{'/* || $val[0] == '['*/)) {
					$json[$name] = json_decode($val, true);
				}
			}
		}

		$value = preg_replace_callback(
			'/@@([\.a-zA-Z_-]+)@@/m',
			function($matches) use ($node, $json) {
				$attrib = var_export(\Vvveb\arrayPath($json, $matches[1]), true);
				$this->debug->log('VTPL_ATTRIBUTE', '<b>JSON NAME</b> ' . $attrib);
				$value = $node->getAttribute($attrib);
				$this->debug->log('VTPL_ATTRIBUTE', '<b>ATTRIB VALUE</b> ' . $value);

				return $attrib;
			},
			$value
		);

		//macros, compile time function calls
		$value = preg_replace_callback(
			'/@@macro ([a-z_A-Z]+)\((.+?)\)@@/',
			function($matches) use (&$node) {
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

					// $params_exp = var_export($params, true);
					return $function(...$params);
				}

				return $matches[0];
			},
			$value
		);

		return $this->processAttributeFilters($value, $node);
	}

	/**
	 * @param Dom\HTMLElement|DOMNode &$node
	 *
	 * @return void
	 */
	/**
	 * Detach a node from the document after emptying it.
	 *
	 * Shared by the delete* modifiers; the parent check makes it safe to
	 * call on already-detached nodes.
	 *
	 * @param Dom\HTMLElement|DOMNode &$node
	 *
	 * @return void
	 */
	private function removeNode(Dom\HTMLElement|DOMNode &$node): void {
		$this->removeChildren($node);

		if ($node->parentNode) {
			$node->parentNode->removeChild($node);
		}
	}

	/**
	 * @param Dom\HTMLElement|DOMNode &$node
	 *
	 * @return void
	 */
	private function removeChildren(Dom\HTMLElement|DOMNode &$node): void {
		while ($node->firstChild) {
			$node->removeChild($node->firstChild);
		}
	}

	/**
	 * Set a node's value based on its tag type.
	 * - input → value attribute
	 * - iframe/video/audio/source → src attribute
	 * - form → action attribute
	 * - default → innerHTML via document fragment
	 *
	 * @param DOMNode $node
	 * @param string  $html
	 *
	 * @return void
	 */
	private function setNodeValueByType(Dom\HTMLElement|DOMNode $node, string $html): void {
		switch ($node->nodeName) {
			case 'input':
				$this->setAttribute($node, 'value', $html);

				break;

			case 'iframe':
			case 'video':
			case 'audio':
			case 'source':
				$this->setAttribute($node, 'src', $html);

				break;

			case 'form':
				$this->setAttribute($node, 'action', $html);

				break;

			default:
				$this->removeChildren($node);
				$f = $this->createXmlFragment($this->processAttributeConstants($html, $node));
				if ($f) {
					$node->appendChild($f);
				}
		}
	}

	/**
	 * @param array<array-key, mixed>|Dom\NodeList<\Dom\Node>|DOMNodeList<\DOMNode> $nodeList
	 * @param false|string                                                          $html
	 *
	 * @return string|null
	 */
	private function innerHTML(array|Dom\NodeList|DOMNodeList $nodeList, false|string $html = false): ?string {
		if ($nodeList) {
			foreach ($nodeList as $node) {
				if ($html === false) {
					$doc = new DOMDocument();

					foreach ($node->childNodes as $child) {
						$doc->appendChild($doc->importNode($child, true));
					}

					return $this->saveDocument($doc);
				} else {
					if ($html === '') {
						continue;
					}

					if ($this->externalElements) {
						$result = $this->loadFromExternalHtml($html, $node);
						$this->removeChildren($node);

						foreach ($result as $externalNode) {
							$importedNode = $this->document->importNode($externalNode, true);
							$node->appendChild($importedNode);
						}
					} else {
						$this->setNodeValueByType($node, $html);
					}
				}
			}
		}

		return null;
	}

	/**
	 * @param Dom\NodeList<\Dom\Node>|DOMNodeList<\DOMNode>|null &$nodeList
	 * @param array<array-key, mixed>|string|null                $html
	 *
	 * @return void
	 */
	private function outerHTML(null|Dom\NodeList|DOMNodeList &$nodeList, null|array|string $html = null): void {
		foreach ($nodeList as $node) {
			if ($html === '') {
				continue;
			}

			if ($this->externalElements) {
				$result = $this->loadFromExternalHtml($html, $node);

				$parent = $node->parentNode;
				$count  = 0;

				if ($result) {
					foreach ($result as $externalNode) {
						$importedNode = $this->document->importNode($externalNode, true);

						if ($parent) {
							if ($count) {
								$parent->appendChild($importedNode);
							} else {
								$parent->replaceChild($importedNode, $node);
							}
							$node = $importedNode;
							$count++;
						}
					}
				}
			} else {
				$f = $this->createXmlFragment($this->processAttributeConstants($html, $node));
				if ($f) {
					$node->parentNode->replaceChild($f, $node);
				}
			}
		}
	}

	/**
	 * @param list<Dom\HTMLElement|DOMNode>|Dom\NodeList<\Dom\Node>|DOMNodeList<\DOMNode>|null $nodeList
	 * @param string|null                                                                      $text
	 *
	 * @return string|null
	 */
	private function innerText(array|Dom\NodeList|DOMNodeList|null $nodeList, ?string $text = null): ?string {
		foreach ($nodeList as $node) {
			if ($text === null) {
				return $node->nodeValue;
			} else {
				if ($node->hasChildNodes()) {
					foreach ($node->childNodes as $childNode) {
						$value = trim($childNode->nodeValue);

						if ($childNode->nodeType === XML_TEXT_NODE && $value !== '') {
							$f = $this->createXmlFragment($this->processAttributeConstants($text, $node));

							if ($f) {
								$node->replaceChild($f, $childNode);
							}

							break;
						}
					}
				} else {
					$this->setNodeValueByType($node, $text);
				}
			}
		}

		return null;
	}

	/**
	 * @param Dom\NodeList<\Dom\Node>|DOMNodeList<\DOMNode>|null &$nodeList
	 * @param false|string                                       $variable
	 *
	 * @return void
	 */
	private function ifExists(null|Dom\NodeList|DOMNodeList &$nodeList, false|string $variable = false): void {
		if ($variable === '') {
			return;
		}

		foreach ($nodeList as $node) {
			$condition = $this->processAttributeConstants($variable, $node);
			$isset = str_replace('!', '', $condition);
			//before
			$f = $this->createXmlFragment(VTPL_PHP_CDATA_OPEN . "if (isset($isset) && $condition) {" . VTPL_PHP_CDATA_CLOSE);
			if ($f) {
				$node->parentNode->insertBefore($f, $node);
			}

			//after
			$f = $this->createXmlFragment(VTPL_PHP_OPEN . '}' . VTPL_PHP_CLOSE);
			if ($f) {
				$node->parentNode->insertBefore($f, $node->nextSibling);
			}
		}
	}

	/**
	 * @param Dom\NodeList<\Dom\Node>|DOMNodeList<\DOMNode>|null &$nodeList
	 * @param false|string                                       $variable
	 *
	 * @return void
	 */
	private function hide(null|Dom\NodeList|DOMNodeList &$nodeList, false|string $variable = false): void {
		if ($variable) {
			$variable = '!' . $variable;
		}

		$this->ifExists($nodeList, $variable);
	}

	/**
	 * @param list<Dom\HTMLElement|DOMNode>|Dom\NodeList<\Dom\Node>|DOMNodeList<\DOMNode>|null &$nodeList
	 * @param false|string                                                                     $open
	 * @param false|string                                                                     $close
	 *
	 * @return bool
	 */
	private function tagWrap(array|Dom\NodeList|DOMNodeList|null &$nodeList, false|string $open = false, false|string $close = false): bool {
		if ($open === '' || $close === '') {
			return false;
		}
		$openStart = VTPL_PHP_CDATA_OPEN . $open . VTPL_PHP_CDATA_CLOSE;
		$openEnd   = VTPL_PHP_OPEN . '}' . VTPL_PHP_CLOSE;

		$closeStart = VTPL_PHP_CDATA_OPEN . $close . VTPL_PHP_CDATA_CLOSE;
		$closeEnd   = VTPL_PHP_OPEN . '}' . VTPL_PHP_CLOSE;

		foreach ($nodeList as $node) {
			//before start
			$f = $this->createXmlFragment($this->processAttributeConstants($openStart, $node));
			if ($f) {
				$node->parentNode->insertBefore($f, $node);
			}

			//before end
			$f = $this->createXmlFragment($this->processAttributeConstants($openEnd, $node));

			if ($node->hasChildNodes()) {
				if ($f) {
					$node->insertBefore($f, $node->firstChild);
				}
			} else {
				if ($f) {
					$node->appendChild($f);
				}
			}

			//after start
			$f = $this->createXmlFragment($this->processAttributeConstants($closeStart, $node));
			if ($f) {
				$node->appendChild($f);
			}

			//after end
			$f = $this->createXmlFragment($this->processAttributeConstants($closeEnd, $node));
			if ($f) {
				$node->parentNode->insertBefore($f, $node->nextSibling);
			}
		}

		return true;
	}

	/**
	 * Resolve external elements for insert/append operations.
	 * If the from() reference is @_SELF_@, queries the current document;
	 * otherwise loads from an external HTML file.
	 *
	 * @param array<array-key, mixed>|string $html
	 * @param DOMNode                        $node
	 *
	 * @return Dom\NodeList<\Dom\Node>|DOMNodeList<\DOMNode>|false
	 */
	private function resolveExternalElements(array|string $html, Dom\HTMLElement|DOMNode $node): Dom\NodeList|DOMNodeList|false {
		if ($this->froms && $this->froms[0][(int)$html[1]] === '@_SELF_@') {
			$selector = $this->froms[2][(int)$html[1]];

			return $this->query($selector);
		}

		return $this->loadFromExternalHtml($html, $node);
	}

	/**
	 * @param Dom\NodeList<\Dom\Node>|DOMNodeList<\DOMNode>|null &$nodeList
	 * @param array<array-key, mixed>|string                     $html
	 *
	 * @return bool
	 */
	private function insertBefore(null|Dom\NodeList|DOMNodeList &$nodeList, array|string $html): bool {
		if ($html === '') {
			return false;
		}

		if ($nodeList) {
			foreach ($nodeList as $node) {
				if ($this->externalElements) {
					$result = $this->resolveExternalElements($html, $node);

					if (!$result) {
						continue;
					}
					foreach ($result as $externalNode) {
						$importedNode = $this->document->importNode($externalNode, true);
						$node->parentNode->insertBefore($importedNode, $node);
					}
				} else {
					$f = $this->createXmlFragment($this->processAttributeConstants($html, $node));
					if ($f) {
						$node->parentNode->insertBefore($f, $node);
					}
				}
			}

			return true;
		}

		return false;
	}

	/**
	 * @param Dom\NodeList<\Dom\Node>|DOMNodeList<\DOMNode>|null &$nodeList
	 * @param array<array-key, mixed>|string                     $html
	 *
	 * @return bool
	 */
	private function insertAfter(null|Dom\NodeList|DOMNodeList &$nodeList, array|string $html): bool {
		if ($html === '') {
			return false;
		}

		if ($nodeList) {
			foreach ($nodeList as $node) {
				if ($this->externalElements) {
					$result = $this->resolveExternalElements($html, $node);

					if (!$result) {
						continue;
					}
					foreach ($result as $externalNode) {
						$importedNode = $this->document->importNode($externalNode, true);

						//importNode returns the same node for nodes already in this document;
						//when the external element is $node's own nextSibling, inserting it
						//before itself throws "Cannot add newnode as the previous sibling of refnode".
						//In that case it is already in the correct position, so skip the move.
						if ($importedNode === $node->nextSibling) {
							continue;
						}
						$node->parentNode->insertBefore($importedNode, $node->nextSibling);
					}
				} else {
					$f = $this->createXmlFragment($this->processAttributeConstants($html, $node));
					if ($f) {
						$node->parentNode->insertBefore($f, $node->nextSibling);
					}
				}
			}

			return true;
		}

		return false;
	}

	/**
	 * @param Dom\NodeList<\Dom\Node>|DOMNodeList<\DOMNode>|null &$nodeList
	 * @param array<array-key, mixed>|string                     $html
	 *
	 * @return bool
	 */
	private function append(null|Dom\NodeList|DOMNodeList &$nodeList, array|string $html): bool {
		if ($html === '') {
			return false;
		}

		if ($nodeList) {
			foreach ($nodeList as $node) {
				if ($this->externalElements) {
					$result = $this->resolveExternalElements($html, $node);

					if (!$result) {
						continue;
					}
					foreach ($result as $externalNode) {
						$importedNode = $this->document->importNode($externalNode, true);
						$node->appendChild($importedNode);
					}
				} else {
					$f = $this->createXmlFragment($this->processAttributeConstants($html, $node));
					if ($f) {
						$node->appendChild($f);
					}
				}
			}

			return true;
		}

		return false;
	}

	/**
	 * @param Dom\NodeList<\Dom\Node>|DOMNodeList<\DOMNode>|null &$nodeList
	 * @param array<array-key, mixed>|string                     $html
	 *
	 * @return bool
	 */
	private function prepend(null|Dom\NodeList|DOMNodeList &$nodeList, array|string $html): bool {
		if (!$html) {
			return false;
		}

		if ($nodeList) {
			foreach ($nodeList as $node) {
				if ($this->externalElements) {
					$result = $this->resolveExternalElements($html, $node);

					if (!$result) {
						continue;
					}

					foreach ($result as $externalNode) {
						$importedNode = $this->document->importNode($externalNode, true);

						if ($importedNode === $node->firstChild) {
							//self-reference already in position, avoid inserting before itself
							continue;
						}

						if ($node->firstChild) {
							// $ref has an immediate sibling : insert newnode before this one
							$node->insertBefore($importedNode, $node->firstChild);
						} else {
							// $ref has no sibling next to him : insert newnode as last child of his parent
							$node->appendChild($importedNode);
						}
					}
				} else {
					$f = $this->createXmlFragment($this->processAttributeConstants($html, $node));

					if ($node->firstChild) {
						// $ref has an immediate sibling : insert newnode before this one
						if ($f) {
							$node->insertBefore($f, $node->firstChild);
						}
					} else {
						// $ref has no sibling next to him : insert newnode as last child of his parent
						if ($f) {
							$node->appendChild($f);
						}
					}
				}
			}

			return true;
		}

		return false;
	}

	/**
	 * @param Dom\NodeList<\Dom\Node>|DOMNodeList<\DOMNode>|null &$nodeList
	 *
	 * @return void
	 */
	private function deleteAllButFirst(null|Dom\NodeList|DOMNodeList &$nodeList): void {
		$first = true;

		if ($nodeList) {
			foreach ($nodeList as $node) {
				if (!$first) {
					$this->removeNode($node);
				}
				$first = false;
			}
		}
	}

	/**
	 * @param Dom\NodeList<\Dom\Node>|DOMNodeList<\DOMNode>|null &$nodeList
	 *
	 * @return void
	 */
	private function deleteAllButFirstChild(null|Dom\NodeList|DOMNodeList &$nodeList): void {
		$parents = [];

		if ($nodeList) {
			foreach ($nodeList as $node) {
				if (in_array($node->parentNode, $parents, true)) {
					$this->removeNode($node);
				}

				if ($node->parentNode) {
					$parents[] = $node->parentNode;
				}
			}
		}
	}

	/**
	 * @param Dom\NodeList<\Dom\Node>|DOMNodeList<\DOMNode>|null &$nodeList
	 *
	 * @return void
	 */
	private function delete(null|Dom\NodeList|DOMNodeList &$nodeList): void {
		if ($nodeList) {
			foreach ($nodeList as $node) {
				$this->removeNode($node);
			}
		}
	}

	/**
	 * @param Dom\HTMLElement|DOMNode $node
	 * @param string                  $attribute
	 * @param string                  $val
	 *
	 * @return void
	 */
	private function setNodeAttribute(Dom\HTMLElement|DOMNode $node, string $attribute, string $val): void {
		if (!$val) {
			return;
		}
		$value = $this->processAttributeConstants($val, $node);
		//if the attribute value has no php in it add it directly
		if ($value) {
			if (!str_contains($value, VTPL_PHP_OPEN)) {
				$node->setAttribute($attribute, $value);
			} else {
				$this->attributes[++$this->attributesIndex] = $value;
				$node->setAttribute($attribute, "@@__VTPL__ATTRIBUTE_PLACEHOLDER__{$this->attributesIndex}@@");
			}
		}
	}

	/**
	 * @param Dom\HTMLElement|DOMNode|Dom\NodeList<\Dom\Node>|DOMNodeList<\DOMNode> &$nodeList
	 * @param string                                                                $attribute
	 * @param string                                                                $val
	 *
	 * @return void
	 */
	private function setAttribute(Dom\HTMLElement|Dom\NodeList|DOMNode|DOMNodeList &$nodeList, string $attribute, string $val): void {
		if (is_a($nodeList, 'DOMNodeList') || is_a($nodeList, 'Dom\NodeList')) {
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

	/**
	 * @param Dom\HTMLElement|DOMNode &$node
	 * @param string                  $val
	 * @param bool                    $processConstants
	 *
	 * @return void
	 */
	private function addNodeClass(Dom\HTMLElement|DOMNode &$node, string $val, bool $processConstants = true): void {
		if ($processConstants) {
			$val =  $this->processAttributeConstants($val, $node);
		}

		$this->attributes[++$this->attributesIndex] = $val;
		$node->setAttribute('class', $node->getAttribute('class') . " @@__VTPL__ATTRIBUTE_PLACEHOLDER__{$this->attributesIndex}@@");
	}

	/**
	 * @param Dom\NodeList<\Dom\Node>|DOMNodeList<\DOMNode> $nodeList
	 * @param string                                        $val
	 *
	 * @return void
	 */
	private function addClass(Dom\NodeList|DOMNodeList $nodeList, string $val): void {
		if (is_a($nodeList, 'DOMNodeList')) {
			if ($nodeList->length > 0) {
				foreach ($nodeList as $node) {
					$this->addNodeClass($node, $val);
				}
			}
		}
	}

	/**
	 * @param Dom\HTMLElement|DOMNode &$node
	 * @param string                  $val
	 *
	 * @return void
	 */
	public function addNodeNewAttribute(Dom\HTMLElement|DOMNode &$node, string $val): void {
		$this->newAttributes[++$this->newAttributesIndex] = $this->processAttributeConstants($val, $node);
		$node->setAttribute("__VTPL__NEW_ATTRIBUTE_PLACEHOLDER__{$this->newAttributesIndex}", '');
	}

	/**
	 * @param array<array-key, mixed>|Dom\NodeList<\Dom\Node>|DOMNodeList<\DOMNode> &$nodeList
	 * @param string                                                                $val
	 *
	 * @return void
	 */
	public function addNewAttribute(array|Dom\NodeList|DOMNodeList &$nodeList, string $val): void {
		if ($nodeList->length > 0) {
			foreach ($nodeList as $node) {
				$this->addNodeNewAttribute($node, $val);
			}
		}
	}

	/**
	 * @param Dom\NodeList<\Dom\Node>|DOMNodeList<\DOMNode> $nodeList
	 * @param string                                        $val
	 *
	 * @return void
	 */
	private function removeAttribute(Dom\NodeList|DOMNodeList $nodeList, string $val): void {
		if ($nodeList->length > 0) {
			foreach ($nodeList as $node) {
				$node->removeAttribute($val);
			}
		}
	}

	/**
	 * @param Dom\NodeList<\Dom\Node>|DOMNodeList<\DOMNode> $nodeList
	 * @param string                                        $val
	 *
	 * @return void
	 */
	private function removeClass(Dom\NodeList|DOMNodeList $nodeList, string $val): void {
		if ($nodeList->length > 0) {
			foreach ($nodeList as $node) {
				$class = $node->getAttribute('class');
				$class = str_replace($val, '', $class);
				$node->setAttribute('class', $class);
			}
		}
	}

	/**
	 * @param array<array-key, mixed>|string $val
	 * @param DOMNode                        $node
	 *
	 * @return Dom\NodeList<\Dom\Node>|DOMNodeList<\DOMNode>|false
	 */
	private function loadFromExternalHtml(array|string $val, Dom\HTMLElement|DOMNode $node): Dom\NodeList|DOMNodeList|false {
		$filename = $this->froms[0][(int)$val[1]]; //external html file
		$selector = $this->froms[2][(int)$val[1]];
		//load specified selector if available otherwise load html with the same selector

		$filename = $this->processAttributeConstants($filename, $node);
		$selector = $this->processAttributeConstants($selector, $node);

		if (str_starts_with($filename, '/plugins/') || str_starts_with($filename, PUBLIC_PATH)) {
			$filename = DIR_ROOT . $filename;
		} elseif ($filename[0] !== '/') {
			$filename = $this->htmlPath . $filename;
		}

		$this->debug->log('SELECTOR_FROM', $filename);

		if (!($html = @file_get_contents($filename))) {
			Vvveb\logError("can't load html $filename");
			$this->debug->log('LOAD', '<b>EXTERNAL ERROR</b> ' . $filename . ' ' . $selector);

			return false;
		}

		$this->debug->log('LOAD', $filename . ' <b>SELECTOR</b> ' . $selector);

		if (VTPL_DONT_ALLOW_PHP) {
			$html = $this->removePhp($html);
		}

		if ($this->minifyHtml === true) {
			$html = $this->minifyHtml($html);
		}

		$document = $this->loadDomFromString($html);

		if ($selector) {
			if ($this->legacy) {
				$xpath         = new DOMXPath($document);
				$xpathSelector = $this->cssToXpath($selector);
				$elements      = $xpath->query($xpathSelector);
			} else {
				$elements = $document->querySelectorAll($selector);
			}
		} else {
			$elements = $document->childNodes;
		}
		//$elements   = $this->query($selector);

		return $elements;
	}

	/**
	 * Processes a node's attributes during the multi-language pass: rewrites
	 * relative media urls against the theme path, removes data-v-* attributes
	 * when configured, converts {$variable} attribute values to php echoes and
	 * wraps translatable placeholder/title values with the translation function.
	 *
	 * @param Dom\HTMLElement|DOMNode $node
	 *
	 * @return void
	 */
	private function processNodeAttributes(Dom\Element|Dom\HTMLElement|DOMNode $node): void {
		if (!$node->hasAttributes()) {
			return;
		}

		$publicLen = strlen(PUBLIC_PATH);

		foreach ($node->attributes as $name => $attrNode) {
			$value = $attrNode->nodeValue;
			$name  = $attrNode->nodeName;

			//set relative media to theme folder
			if ($this->relativePath) {
				if (($name === 'src' || $name === 'href' || $name === 'srcset') && $node->nodeName !== 'A') {
					if ($value && $value[0] !== '#'
						&& $value[0] !== '@'
						&& !str_starts_with($value, '//')
							&& !str_starts_with($value, '/feed/')
							&& (!str_contains($value, ':'))) {
						if ($value[0] === '/') {
							if (str_starts_with($value, PUBLIC_PATH)) {
								$value = substr($value, $publicLen);
							} else {
								$value = substr($value, 1);
							}

							$this->setNodeAttribute($node, $name, PUBLIC_PATH . $value);
						} else {
							$this->setNodeAttribute($node, $name, $this->relativePath . $value);
						}
					}
				}

				if ($name === 'style' && str_contains($value, 'url(')) {
					$value = preg_replace_callback(
						'/url\(([^\)]+)\)/',
						function($matches) use ($publicLen) {
							$url = trim($matches[1], ' \'"');

							if (str_starts_with($url, 'http')) {
								return 'url(' . $url . ')';
							}

							if ($url[0] === '/') {
								if (str_starts_with($url, PUBLIC_PATH)) {
									$url = substr($url, $publicLen);
								} else {
									$url = substr($url, 1);
								}

								$url = PUBLIC_PATH . $url;
							} else {
								$url = $this->relativePath . $url;
							}

							return 'url(' . $url . ')';
						},
						$value
					);

					$this->setNodeAttribute($node, $name, $value);
				}
			}

			if ($this->removeVattrs && str_starts_with($name, 'data-v-')) {
				$node->removeAttribute($name);

				continue;
			} else {
				if (str_contains($value, '$')) {
					$value = preg_replace_callback(
						'/{(\$[a-z][\w\.>\-]+)}/i',
						function($matches) {
							$value = $matches[1];

							if (str_starts_with($value, '$this')) {
								$value = str_replace('$this.', '$this->', $value);
							}
							$value   = Vvveb\dotToArrayKey($value);

							return VTPL_PHP_CDATA_OPEN . 'if (isset(' . $value . ')) echo htmlspecialchars(' . $value . ');' . VTPL_PHP_CDATA_CLOSE;
						},
						$value
					);

					$this->setNodeAttribute($node, $name, $value);
				}
			}

			if (($name === 'placeholder' || $name === 'title'/* || $name == 'alt'*/) && ($value && $value[0] !== '@' && $value[0] !== '<')) {
				$trimmed = trim($value);

				if ($trimmed) {
					$php     = VTPL_PHP_CDATA_OPEN . 'echo ' . $this->translationFunction . '(\'' . addcslashes($trimmed, "'") . '\');' . VTPL_PHP_CDATA_CLOSE;
					$this->setNodeAttribute($node, $name, $php);
				}
			}
			/*
			if (strpos($name, 'data-v-') === 0) {
				$node->removeAttribute($name);
			}*/
		}
	}

	/**
	 * @param Dom\HTMLElement|DOMNode $currentNode
	 *
	 * @return void
	 */
	private function setMultiLanguageText(Dom\Node|DOMNode $currentNode): void {
		if ($currentNode->childNodes->length === 0) {
			return;
		}

		$length    = $currentNode->childNodes->length;
		$publicLen = strlen(PUBLIC_PATH);
		for ($i = 0; $i < $length; $i++) {
			$node = $currentNode->childNodes[$i];
			if (!$node) {
				continue;
			}
			//strip comments
			if ($node->nodeType === XML_COMMENT_NODE && $this->removeComments) {
				$node->parentNode->removeChild($node);
				$i--;
				continue;
			}

			//process media urls, data-v-* attrs, {$variables} and translatable placeholders
			if (property_exists($node, 'attributes') || (method_exists($node, 'hasAttributes') && $node->hasAttributes())) {
				$this->processNodeAttributes($node);
			}

			$tagName = isset($node->parentNode->tagName) ? strtolower($node->parentNode->tagName) : false;

			if ($node && $node->nodeType === XML_TEXT_NODE
				&& (!$tagName || ($tagName !== 'textarea' && $tagName !== '_script' && $tagName !== 'script'
					&& $tagName !== 'code' && $tagName !== 'iframe' && $tagName !== 'html'
					&& $tagName !== 'noscript'))) {
				if (isset($node->wholeText)) {
					$text = $node->wholeText;
				} else {
					$text = $node->textContent;
				}

				$text    = \Vvveb\stripExtraSpaces($text);
				$trimmed = trim($text);

				//skip untranslatable text
				if (strlen($trimmed) < 2 //too small
					|| str_starts_with($trimmed, '{$') //starts with variable
					|| str_starts_with($trimmed, 'http') //is url
					|| str_contains($trimmed, '{$') //string has variable
					|| str_contains($trimmed, '<?php') //string has php code
					|| (is_numeric($trimmed[0]))
				) {
					continue;
				}

				$before = $currentNode->childNodes->length;

				//don't translate long texts
				if (strlen($trimmed) < 1024) {
					if ($this->legacy) {
						$c = $this->document->createCDATASection('echo ' . $this->translationFunction . '(\'' . addcslashes($trimmed, "'") . '\');');
					} else {
						$c = $this->document->createTextNode('echo ' . $this->translationFunction . '(\'' . addcslashes($trimmed, "'") . '\');');
					}
					$f = $this->document->createElement('_script');
					$f->setAttribute('language', 'php');
					$f->appendChild($c);
					$node->parentNode->replaceChild($f, $node);

					//add previously trimmed space around text
					$space   = str_replace($trimmed, '|', $text);
					$explode = explode('|', $space);
					//space before text
					if ($explode[0]) {
						$s = $this->document->createTextNode($explode[0]);
						$f->parentNode->insertBefore($s, $f);
					}
					//space after text
					if (isset($explode[1]) && $explode[1]) {
						$e = $this->document->createTextNode($explode[1]);
						//php 8
						if (isset($f->nextElementSibling)) {
							//add before next element
							$f->parentNode->insertBefore($e, $f->nextSibling);
						} else {
							if ($f->nextSibling) {
								//add before next element
								$f->parentNode->insertBefore($e, $f->nextSibling);
							} else {
								//add as last element
								$f->parentNode->appendChild($e);
							}
						}
					}
				}

				$diff = $currentNode->childNodes->length - $before;
				$length += $diff;
			} else {
				$this->setMultiLanguageText($node);
			}
		}
	}

	/**
	 * @param string $php
	 *
	 * @return string
	 */
	private function minifyPhp(string $php): string {
		if (function_exists('token_get_all')) {
			//tokenize so // and /* inside strings (e.g. "https://host") are not stripped as comments
			$tokens = token_get_all('<?php ' . $php);
			$clean  = '';
			foreach ($tokens as $token) {
				if (is_array($token) && ($token[0] === T_DOC_COMMENT || ($token[0] === T_COMMENT && !str_starts_with($token[1], '#')))) {
					continue;
				}
				$clean .= is_array($token) ? $token[1] : $token;
			}
			$php = preg_replace('/^<\?php\s*/', '', $clean, 1);
		} else {
			//php comments outside strings /* */
			$php = preg_replace('/(?<!["\'])\/\*.*?\*\//s', '', $php);

			//php comments outside strings //
			$php = preg_replace('@(\/\/)(?=(?:[^"\']|["\'][^"\']*["\'])*$)[^\n]*@s', '', $php);
		}

		//repeating spaces
		$php = preg_replace('/\s+/', ' ', $php);

		//repeating end lines
		return preg_replace('/\n+/', '', $php);
	}

	/**
	 * @param string $html
	 *
	 * @return string
	 */
	private function removePhp(string $html): string {
		//hack, php allows different opening and closing tags
		return preg_replace('@(<\?php|<\? |<\?=|<\s*script\s*language\s*=\s*"\s*php\s*"\s*>|<%[^%]*%>).*?(\?>|<\s*/\s*script\s*>|%>)@sm', '', $html);
	}

	/**
	 * @param string $html
	 *
	 * @return string
	 */
	private function minifyHtml(string $html): string {
		//html comments but keep ie conditionals
		$html = preg_replace('/<!--(?!\s*\[if\s)(?!@@_KEEP_COMMENT_@@)(.*?)-->/sm', '', $html);
		$html = str_replace('<!--@@_KEEP_COMMENT_@@', '<!--', $html);

		//repeating spaces
		$html = preg_replace('/\s+/', ' ', $html);

		//repeating end lines
		$html = preg_replace('/\n+/', "\n", $html);
		$html = preg_replace('@> </@', '></', $html);
		$html = preg_replace('/ "/', '"', $html);
		$html = preg_replace('/" /', '"', $html);

		//repeating spaces
		return preg_replace('/\s+/', ' ', $html);
	}

	/**
	 * @param string|null &$html
	 *
	 * @return void
	 */
	private function cleanHtml(?string &$html): void {
		if ($html === null) {
			return;
		}

		$html = preg_replace_callback(
			'/@@__VTPL__ATTRIBUTE_PLACEHOLDER__(\d+)@@/',
			fn ($matches) => $this->attributes[$matches[1]],
			$html
		); //sad hack :(

		$html = preg_replace_callback(
			'/__VTPL__NEW_ATTRIBUTE_PLACEHOLDER__(\d+)=""/',
			fn ($matches) => $this->newAttributes[$matches[1]],
			$html
		); //sad hack :(

		$html = preg_replace_callback(
			'/<script(.*?)holder="@@__VTPL__SCRIPT_PLACEHOLDER__(\d+)@@"(.*?)><\/script>/',
			function($matches) {
				if (VTPL_JS_MINIFY) {
					$script = $this->minifyJs($this->scripts[$matches[2]]);
				} else {
					$script = $this->scripts[$matches[2]];
				}

				return '<script' . $matches[1] . ' ' . $matches[3] . '>' . $script . '</script>';
			},
			$html
		);

		//cleanup modified scripts
		$html = preg_replace('/<script.*?holder="@@__VTPL__SCRIPT_PLACEHOLDER__(\d+)@@"[^>]*>/', '', $html);

		// Constants, replace @@_CONSTANT_NAME_@@ with the value defined for CONSTANT_NAME = 'value'
		$html = preg_replace_callback(
			'/@@_CONSTANT_([A-Z_]*)_@@/',
			function($matches) {
				//constant may be referenced but never defined - emit nothing instead of warning/null
				return $this->constants[$matches[0]] ?? '';
			},
			$html
		);

		/*
		 Moustache variables used in javascript code in html template, it replaces $variable with $variable, if $variable is array then the output is json
		 Modifier support with $variable|function_name ex: $var|htmlspecialchars multiple modifiers $var|htmlspecialchars|trim
		 */
		$html = preg_replace_callback(
			'/{\s*(\$[\w\-\>\.]+)\|?([\w\.\|]+)?\s*}/',
			function($matches) {
				$modifier = false;

				$variable = str_replace('$this.', '$this->', $matches[1]);
				$variable = Vvveb\dotToArrayKey($variable);

				if (isset($matches[2]) && $matches[2]) {
					$modifier  = $variable;
					$modifiers = explode('|', $matches[2]);

					foreach ($modifiers as $mod) {
						$modifier = "$mod($modifier)";
					}
				}

				if ($modifier) {
					if ($this->escapeInline) {
						$escaped = 'htmlspecialchars($modified)';
					} else {
						$escaped = '$modified';
					}

					$template
					 = "<?php if (isset($variable)) {
									\$modified = $modifier;
									if (is_array($variable)) {
										echo json_encode(\$modified, JSON_HEX_TAG);
									} else {
										echo $escaped;
									}
								}
							?>";
				} else {
					if ($this->escapeInline) {
						$escaped = "htmlspecialchars($variable)";
					} else {
						$escaped = $variable;
					}

					$template
					 = "<?php if (isset($variable)) {
									if (is_array($variable)) {
										echo json_encode($variable, JSON_HEX_TAG);
									} else {
										echo $variable;
									}
								}
							?>";
				}

				return $template;
			},
			$html
		);

		$html = str_replace([VTPL_PHP_CDATA_OPEN, VTPL_PHP_CDATA_CLOSE, VTPL_PHP_OPEN, VTPL_PHP_CLOSE], ['<?php ', ' ?>', '<?php ', ' ?>'], $html);
		//decode html entities to match translation strings
		$translationFunction = preg_quote($this->translationFunction);
		$html                = preg_replace_callback(
			"/<\\?php echo {$translationFunction}\\('(.+?)'\\); \\?>/",
			function($matches) {
				$translation = html_entity_decode($matches[1], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5);
				//keep <> encoded to avoid xss
				$translation = str_replace(['<', '>'], ['&lt;', '&gt;'], $translation);

				return "<?php echo {$this->translationFunction}('{$translation}'); ?>";
			},
			$html
		);

		//remove empty value for data attributes

		//remove data-v- attributes
		if ($this->removeVattrs) {
		}
	}

	/**
	 * Creates a document fragment from an xml string, or null when the fragment
	 * would be empty (appendXML on empty/invalid xml yields no children).
	 *
	 * @param string $xml
	 *
	 * @return ?DOMDocumentFragment
	 */
	private function createXmlFragment(string $xml): null|Dom\DocumentFragment|DOMDocumentFragment {
		$f = $this->document->createDocumentFragment();
		$f->appendXML($xml);

		return $f->firstChild ? $f : null;
	}

	/**
	 * Creates a DOMDocument|Dom\Document and loads a string as html or xml based on documentType.
	 *
	 * @param string $html
	 * @param int    $options libxml option flags for the xml branch
	 *
	 * @return Dom\Document|DOMDocument
	 */
	private function loadDomFromString(string $html, int $options = LIBXML_NOERROR): Dom\Document|DOMDocument {
		if ($this->documentType === 'html') {
			if ($this->legacy) {
				$document = new DOMDocument();
				@$document->loadHTML($html, $options);
			} else {
				$document = \Dom\HTMLDocument::createFromString($html);
			}
		} else {
			$document = new DOMDocument();
			@$document->loadXML($html, $options);
		}

		return $document;
	}

	/**
	 * Create a DOMDocument with the same options used by the engine.
	 *
	 * @return DOMDocument
	 */
	private function createDomDocument(): DOMDocument {
		$dom                      = new DOMDocument();
		$this->configureDomDocument($dom);
		$dom->preserveWhiteSpace = false; //override for extraction

		return $dom;
	}

	/**
	 * Serialize a DOMDocument to HTML or XML string based on documentType.
	 *
	 * @param DOMDocument $dom
	 *
	 * @return string
	 */
	private function saveDocument(Dom\Document|DOMDocument $dom): string {
		if ($this->documentType === 'html') {
			return $dom->saveHTML();
		}

		return Vvveb\xmlStripVersionDeclr($dom->saveXML());
	}

	/**
	 * @param string $compiledFile
	 *
	 * @return bool
	 */
	public function saveCompiledTemplate(string $compiledFile): bool {
		$this->processTemplateFile();
		$this->processTemplate();
		$this->setMultiLanguageText($this->document);

		if ($this->selector) {
			//extract only the specified part
			$elements      = $this->query($this->selector);
			$componentNode = $elements ? $elements->item($this->componentId) : null;

			if ($componentNode) {
				$tmpDom = $this->createDomDocument();

				//add before php code if available
				$currentNode = $componentNode;

				while (($currentNode = $currentNode->previousSibling) && $currentNode->nodeName === '_script') {
					$tmpDom->appendChild($tmpDom->importNode($currentNode, true));
				}

				$tmpDom->appendChild($tmpDom->importNode($componentNode, true));

				//add after php code if available
				$currentNode = $componentNode;

				while (($currentNode = $currentNode->nextSibling) && $currentNode->nodeName === '_script') {
					$tmpDom->appendChild($tmpDom->importNode($currentNode, true));
				}

				$html = trim($this->saveDocument($tmpDom));
			} else {
				$html = $this->saveDocument($this->document);
			}
		} else {
			$html = $this->saveDocument($this->document);
		}

		$this->cleanHtml($html);

		$this->debug->log('SAVE', $compiledFile);

		//show debug console if needed
		if ($this->debug->enabled()) {
			$this->debug->printLog();
		}

		if (empty($html)) {
			Vvveb\logError("compiled template is empty for $compiledFile");

			return false;
		}

		if ($this->checkSyntax && function_exists('token_get_all')) {
			try {
				//validate before writing so a broken template never poisons the compiled cache
				$tokens = token_get_all($html, TOKEN_PARSE);
				unset($tokens);
			} catch (\Error|\ParseError $e) {
				$data = \Vvveb\System\Core\exceptionToArray($e, $compiledFile);
				file_put_contents($compiledFile, $html);

				\Vvveb\System\Core\FrontController::notFound(false, $data, 500);
			}
		}

		file_put_contents($compiledFile, $html);

		return true;
	}

	/**
	 * @param string $html
	 *
	 * @return array<array-key, mixed>
	 */
	public function loadHtml(string $html): array {
		if (VTPL_DONT_ALLOW_PHP) {
			$html = $this->removePhp($html);
		}
		//replace script tags with placeholders to preserve formatting.

		preg_match_all('/<script(.*?)>(.*?)<\/script>/smix', $html, $this->scripts);
		$count          = count($this->scripts[0]);

		if ($count) {
			$patternsScripts     = [];
			$placeholdersScripts = [];

			for ($i = 0; $i < $count; $i++) {
				$patternsScripts[]     = $this->scripts[0][$i];
				$placeholdersScripts[] = '<script ' . $this->scripts[1][$i] . ' holder="@@__VTPL__SCRIPT_PLACEHOLDER__' . $i . '@@"></script>';
			}

			$html           = str_replace($patternsScripts, $placeholdersScripts, $html);
			$this->scripts  = $this->scripts[2];
		}

		if ($this->minifyHtml === true) {
			$html = $this->minifyHtml($html);
		}
		//replace constants
		if ($this->replaceConstants) {
			$html = str_replace(array_keys($this->replaceConstants), array_values($this->replaceConstants), $html);
		}

		if ($this->documentType === 'html') {
			libxml_clear_errors();

			if ($this->legacy) {
				@$this->document->loadHTML($html, LIBXML_NOERROR);
			} else {
				$this->document = \Dom\HTMLDocument::createFromString($html);
			}

			//@\Dom\HTMLDocument::createFromString($html, LIBXML_NOERROR);
		} else {
			//convert json
			if ($this->documentType === 'json') {
				//remove json comments from line start
				$html = Vvveb\removeJsonComments($html);
				$json = json_decode($html, true);
				$json = Vvveb\prepareJson($json);
				$xml  = Vvveb\array2xml($json);
				$html = $xml;
			}
			libxml_clear_errors();
			@$this->document->loadXML($html, LIBXML_NOERROR | LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
		}

		$errors = libxml_get_errors();
		//original document used to extract selectors
		if ($this->legacy) {
			$this->xpath = new DOMXPath($this->document);

			if ($rootNamespace = $this->document->lookupNamespaceUri($this->document->namespaceURI)) {
				$this->xpath->registerNamespace('x', $rootNamespace);
			}
		}
		if ($this->componentContent) {
			//replace component content from page with the one provided
			//$elements = $this->xpath->query($this->cssToXpath($this->selector));
			$elements = $this->query($this->selector);

			if ($elements) {
				$node     = $elements->item($this->componentId);

				if ($node && $node->parentNode) {
					//$html = '<div>asdasdasdasd<div>';
					//$html = $this->processAttributeConstants($this->componentContent, $node);

					$tmpDom       = $this->loadDomFromString($this->componentContent);
					$body         = $tmpDom->getElementsByTagName('body');
					$nodeToImport = $body->item(0)->firstChild ?? null;

					if (!$nodeToImport) {
						Vvveb\logError("component content for selector {$this->selector} is empty");

						return $errors;
					}

					$importNode = $this->document->importNode($nodeToImport, true);
					$node->parentNode->replaceChild($importNode, $node);
				}
			}
		}

		//add base tag if missing
		$base = $this->document->getElementsByTagName('base');

		if ($base->length === 0) {
			$head = $this->document->getElementsByTagName('head');

			if ($head->length > 0) {
				$base = $this->document->createElement('base');
				$base->setAttribute('href', '');
				$head->item(0)->insertBefore($base, $head->item(0)->firstChild);
			}
		} else {
			$base[0]->setAttribute('href', '');
		}

		return $errors;
	}

	/**
	 * @param string $filename
	 *
	 * @return array<array-key, mixed> Array with xml error messages, empty if no errors
	 */
	public function loadHtmlTemplate(string $filename): array {
		$extension          = strtolower(trim(substr($filename, -4), '.'));
		$this->documentType = $extension;

		if (!str_contains($filename, DS)) {
			$filename = $this->htmlPath . $filename;
		}

		if (!($html = @file_get_contents($filename))) {
			Vvveb\logError("can't load template $filename");
			$this->debug->log('LOAD', '<b>ERROR</b> ' . $filename);

			return [];
		}

		$this->debug->log('LOAD', $filename);

		return $this->loadHtml($html);
	}
}
