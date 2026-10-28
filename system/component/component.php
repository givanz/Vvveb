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

namespace Vvveb\System\Component;

use function Vvveb\dashesToCamelCase;
use function Vvveb\removeJsonComments;
use Vvveb\System\Cache;
use Vvveb\System\Core\View;

if (!defined('COMPONENT_CACHE_FLAG_LOCK')) {
	define('COMPONENT_CACHE_FLAG_LOCK', -10001);
	define('COMPONENT_CACHE_FLAG_REGENERATE', -10002);

	//time
	define('COMPONENT_CACHE_EXPIRE_DELAY', 5); //real expiration time +5 seconds
	define('COMPONENT_CACHE_WAIT', 1); //wait for cache generation
	define('COMPONENT_CACHE_MAX_WAIT_RETRY', 3); //wait for cache generation
	define('COMPONENT_CACHE_LOCK_EXPIRE', 20); //lock can not be set more than COMPONENT_CACHE_LOCK_EXPIRE seconds
	define('COMPONENT_CACHE_EXPIRE', 20 * 3600);
}

/**
 * Discovers data components declared in templates via data-v-component-* attributes,
 * instantiates them (with a per-cache-key shared-results cache including a
 * lock/regenerate protocol) and stores their results on the view.
 */
class Component {
	/** @var self|null Singleton instance. */
	private static ?self $instance = null;

	/** @var array<string, list<array<string, mixed>>>|null Parsed component definitions keyed by component name. */
	private ?array $components = [];

	/** @var string|null Path to the cached component definitions file. */
	private ?string $componentsFile = null;

	/** @var bool Whether component definitions have been loaded. */
	private bool $loaded = false;

	/** @var string|false|null Raw page content to parse for components. */
	private string|false|null $content = false;

	/** @var View View instance for template access. */
	private View $view;

	/** @var string Application context (e.g. 'app', 'admin'). */
	private string $app = 'app';

	/** @var string Document type of the template ('html', 'xml', 'json'). */
	private string $documentType = 'html';

	/** @var bool Whether to use component caching. */
	private bool $cache = true;

	/** @var array<string, array<string, \DOMXPath|\DOMNodeList<\DOMNode>>> Cached copy-from element results keyed by file and selector. */
	private array $copyFromElements = [];

	/**
	 * Get or create the singleton Component instance.
	 *
	 * @param View|false|null $view       View instance to use, or false/null to obtain the default.
	 * @param bool            $regenerate Force regeneration of component definitions.
	 * @param string|null     $content    Raw HTML/XML content to parse instead of a template file.
	 * @param string|null     $app        Application context override.
	 *
	 * @return self The singleton Component instance.
	 */
	public static function getInstance(View|false|null $view = false, bool $regenerate = false, ?string $content = null, ?string $app = null): self {
		if (self::$instance === null) {
			if (!$view) {
				$view = View::getInstance();
			}
			self::$instance = new self($view, $regenerate, $content, $app);
		}

		return self::$instance;
	}

	/**
	 * Parse templates, discover components, and optionally cache the definitions.
	 *
	 * @param View|false  $view       View instance providing template paths.
	 * @param bool        $regenerate Force regeneration of the component cache file.
	 * @param string|null $content    Raw content to parse instead of loading a template file.
	 * @param string|null $app        Application context override (defaults to APP constant).
	 */
	public function __construct(View|false $view, bool $regenerate = false, ?string $content = null, ?string $app = null) {
		if ($this->loaded) {
			return;
		}

		$this->app = $app ?? APP;

		if (!$view) {
			$view = View::getInstance();
		}

		$this->view           = $view;
		$this->componentsFile = $view->serviceTemplate() . '.component';
		$this->content        = $content;

		if ((!file_exists($this->componentsFile)) || $regenerate) {
			$this->generateRequiredComponents();
			$this->saveTemplateComponents();
			$this->loadComponents();
			$this->loaded = true;

			return;
		}

		$this->loadTemplateComponents();
		$this->loadComponents();
		$this->loaded = true;
	}

	/**
	 * Parse a "plugin-{plugin}-{namespace}" component name into its parts.
	 *
	 * @param string $name Component name.
	 *
	 * @return array{plugin_name: string, namespace: string, class: string, file: string}|null Null when the name is not a plugin component.
	 */
	public function pluginComponentDetails(string $name): ?array {
		if (!str_starts_with($name, 'plugin')) {
			return null;
		}

		$template   = str_replace('plugin', '', $name);
		$p          = strrpos($template, '-');

		if ($p === false) {
			return null;
		}
		$pluginName = substr($template, 1, $p - 1);
		$nameSpace  = substr($template, $p + 1);

		$pluginClassName = dashesToCamelCase($pluginName);

		return [
			'plugin_name' => $pluginName,
			'namespace'   => $nameSpace,
			'class'       => "Vvveb\\Plugins\\$pluginClassName\\Component\\$nameSpace",
			'file'        => DIR_PLUGINS . "$pluginName/component/" . str_replace('-', '/', $nameSpace) . '.php',
		];
	}

	/**
	 * Resolve the class name and definition file for a component name.
	 *
	 * Core components live in app/component/ (or admin/component/); plugin
	 * components in plugins/{plugin}/component/.
	 *
	 * @param string $component    Component name (e.g. 'posts', 'plugin-saas-sites').
	 * @param bool   $ucfirstClass Whether to ucfirst() the core component class segment.
	 *
	 * @return array{class: string, file: string}
	 */
	private function resolveComponentClass(string $component, bool $ucfirstClass = false): array {
		$details = $this->pluginComponentDetails($component);

		if ($details) {
			return ['class' => $details['class'], 'file' => $details['file']];
		}

		$segments = str_replace('-', '\\', $component);
		if ($ucfirstClass) {
			$segments = ucfirst($segments);
		}

		return [
			'class' => '\Vvveb\Component\\' . $segments,
			'file'  => DIR_ROOT . $this->app . DS . 'component' . DS . str_replace('-', '/', $component) . '.php',
		];
	}

	/**
	 * Convert a component name to the underscore form used for view storage.
	 *
	 * @param string $component
	 */
	private function normalizeComponentName(string $component): string {
		return str_replace('-', '_', $component);
	}

	/**
	 * Check whether a component with the given name exists in the parsed definitions.
	 *
	 * @param string $name Component name (e.g. 'content-blog').
	 *
	 * @return bool True if the component is defined.
	 */
	public function isComponent(string $name): bool {
		return isset($this->components[$name]);
	}

	/**
	 * Retrieve the parsed definition array for a component.
	 *
	 * @param string $name Component name.
	 *
	 * @return list<array<string, mixed>> Component instances and options, or empty array if not found.
	 */
	public function getComponent(string $name): array {
		return $this->components[$name] ?? [];
	}

	/**
	 * Assign a component's results on the view under its normalized name.
	 *
	 * @param View   $view
	 * @param string $component
	 * @param mixed  $index
	 * @param mixed  $results
	 */
	private function assignResults(View $view, string $component, $index, &$results): void {
		$component    = $this->normalizeComponentName($component);
		$comp         = &$view->_component[$component];
		$comp[$index] = $results;
	}

	/**
	 * Check whether results flagged a not-found condition.
	 *
	 * @param mixed $results
	 */
	private function isNotFound($results): bool {
		return isset($results['404']) && $results['404'] === true;
	}

	/**
	 * Load, cache, and instantiate all discovered components, then dispatch request callbacks.
	 *
	 * @return void
	 */
	public function loadComponents(): void {
		$view        = $this->view;
		$notFound404 = false;

		if (is_array($this->components)) {
			[$cache, $objs, $notFound404] = $this->instantiateComponents($notFound404);

			if ($this->cache) {
				$this->processComponentCache($cache, $objs, $notFound404);
			}
		}

		$this->dispatchComponentRequests();

		$this->components = null;

		if ($notFound404) {
			\Vvveb\System\Core\FrontController::notFound(false);
		}
	}

	/**
	 * Include every component class file and instantiate its objects; components
	 * without caching get their results fetched immediately into the view.
	 *
	 * @param bool $notFound404 Not-found accumulator (updated by reference).
	 *
	 * @return array{0: array<array-key, mixed>, 1: array<array-key, mixed>, 2: bool} [$cache, $objs, $notFound404]
	 */
	private function instantiateComponents(bool $notFound404 = false): array {
		$view  = $this->view;
		$objs  = [];
		$cache = [];

		foreach ($this->components ?? [] as $component => $instances) {
			['class' => $class, 'file' => $file] = $this->resolveComponentClass($component);
			$component                           = $this->normalizeComponentName($component);

			if (!file_exists($file)) {
				continue;
			}

			include_once $file;

			foreach ($instances as $instance => $options) {
				$obj = new $class($options);

				if ($obj->cacheExpire && $this->cache && ($cacheKey = $obj->cacheKey())) {
					$cache[]                         = $cacheKey;
					$cache[]                         = $cacheKey . '_expire';
					$obj->component                  = $component;
					$objs[$obj->cacheKey][$instance] = $obj;
				} else {
					$results = $obj->results();

					if ($results !== false && $results !== null) {
						$results['_instance'] = $obj;
					}

					$this->assignResults($view, $component, $instance, $results);

					if ($this->isNotFound($results)) {
						$notFound404 = true;
					}
				}
			}
		}

		return [$cache, $objs, $notFound404];
	}

	/**
	 * Read cached results for all cached component objects, hydrate the view,
	 * mark entries that need regeneration (setting a distributed lock while
	 * regenerating) and wait for other processes that hold a lock.
	 *
	 * @param array<array-key, mixed> $cache       Cache keys (keys interleaved with their "_expire" keys).
	 * @param array<array-key, mixed> $objs        Cached component objects grouped by cache key.
	 * @param bool                    $notFound404 Not-found accumulator (updated by reference).
	 *
	 * @return void
	 */
	private function processComponentCache(array $cache, array $objs, bool &$notFound404): void {
		$view      = $this->view;
		$namespace = 'component';

		//get cached data
		$cacheDriver = Cache::getInstance();
		$data        = $cacheDriver->getMulti($namespace, $cache, SITE_ID);

		foreach ($objs as $cacheKey => $instances) {
			foreach ($instances as $index => $instance) {
				if (!isset($data[$cacheKey]) || !is_array($data[$cacheKey])) {
					$data[$cacheKey] = [];
				}

				$data[$cacheKey]['_instance'] = $instance;
				$this->assignResults($view, $instance->component, $index, $data[$cacheKey]);

				if (isset($data[$cacheKey]['404']) && $data[$cacheKey]['404'] === true) {
					$notFound404 = true;
				}
			}

			$cacheExpireKey = $cacheKey . '_expire';

			//if no lock set and cache is expiring then set lock and queue regeneration
			if (!isset($data[$cacheExpireKey]) ||
				($data[$cacheExpireKey] && ($data[$cacheExpireKey] > 0) &&
				($data[$cacheExpireKey] + COMPONENT_CACHE_EXPIRE_DELAY) < $_SERVER['REQUEST_TIME'])) {
				ignore_user_abort();
				$cacheDriver->set($namespace, $cacheExpireKey, COMPONENT_CACHE_FLAG_LOCK, COMPONENT_CACHE_LOCK_EXPIRE); //set lock
				$data[$cacheExpireKey] = COMPONENT_CACHE_FLAG_REGENERATE; //set regeneration flag
			}

			if ($data[$cacheExpireKey] > 0) {
				//fresh cache, remove from sql regeneration queue
				unset($objs[$cacheKey], $cache[$cacheKey], $cache[$cacheExpireKey], $data[$cacheKey], $data[$cacheExpireKey]);
			}
		}

		$retry = 0;
		//run sql queries for uncached components
		$saveCache = [];

		do {
			$retry++;

			$wait = $this->regenerateUncachedComponents($objs, $data, $saveCache, $notFound404);

			if ($wait) {
				if (defined('DEBUG') && DEBUG) {
					error_log('wait cache ' . ($_SERVER['HTTP_HOST'] ?? '') . ($_SERVER['REQUEST_URI'] ?? ''));
				}

				@sleep(COMPONENT_CACHE_WAIT);
				$data = $cacheDriver->getMulti($namespace, $cache, SITE_ID);
			}
		} while ($wait && $retry < COMPONENT_CACHE_MAX_WAIT_RETRY);

		if ($retry >= COMPONENT_CACHE_MAX_WAIT_RETRY) {
			error_log('error:CACHE max retry reached for ' . ($_SERVER['HTTP_HOST'] ?? '') . ($_SERVER['REQUEST_URI'] ?? ''));
		}

		if (!empty($saveCache)) {
			$cacheDriver->setMulti($namespace, $saveCache, COMPONENT_CACHE_EXPIRE, SITE_ID);
		}
	}

	/**
	 * Run results() for components queued for regeneration; components locked
	 * by another request are left for a later retry pass.
	 *
	 * @param array<array-key, mixed> $objs        Cached component objects grouped by cache key (by value).
	 * @param array<array-key, mixed> $data        Cached data (updated by reference).
	 * @param array<array-key, mixed> $saveCache   Results to persist (updated by reference).
	 * @param bool                    $notFound404 Not-found accumulator (updated by reference).
	 *
	 * @return bool True when some component was locked and another pass is needed.
	 */
	private function regenerateUncachedComponents(array $objs, array &$data, array &$saveCache, bool &$notFound404): bool {
		$view = $this->view;
		$wait = false;

		foreach ($objs as $key => $objects) {
			$cacheExpireKey = $key . '_expire';

			//check lock
			if ((!isset($data[$cacheExpireKey]) || !($data[$cacheExpireKey])) ||
				!isset($data[$key]) ||
				$data[$cacheExpireKey] === COMPONENT_CACHE_FLAG_REGENERATE) {
				//lock set by this script, regenerate content
				$id      = key($objects);

				$results = $objects[$id]->results();

				if ($results !== null) {
					$saveCache[$key]            = $results;
					$saveCache[$cacheExpireKey] = $objects[$id]->cacheExpire;
				}

				if ($this->isNotFound($results)) {
					$notFound404 = true;
				}

				$data[$cacheExpireKey] = 0;

				foreach ($objects as $index => $instance) {
					$results['_instance'] = $instance;
					$this->assignResults($view, $instance->component, $index, $results);
				}
			} elseif ($data[$cacheExpireKey] === COMPONENT_CACHE_FLAG_LOCK) {
				//item is locked, some other script is generating content
				$wait = true;
			}
		}

		return $wait;
	}

	/**
	 * Call the request() callback of every component instance that has results.
	 */
	private function dispatchComponentRequests(): void {
		$view = $this->view;

		foreach ($this->components ?? [] as $component => $instances) {
			$component = $this->normalizeComponentName($component);

			if (!isset($view->_component[$component])) {
				continue;
			}

			$comp = &$view->_component[$component];

			foreach ($instances as $index => $options) {
				$results = &$comp[$index];
				$object  = $results['_instance'] ?? false;

				if ($object && method_exists($object, 'request')) {
					$object->request($results, $index);
				}
			}
		}
	}

	/**
	 * Get component results (stub for subclasses).
	 *
	 * @return mixed
	 */
	public function results(): mixed {
		return null;
	}

	/**
	 * Create a DOM document configured for lenient template parsing.
	 */
	private function createDomDocument(): \DOMDocument {
		$document                      = new \DOMDocument();
		$document->preserveWhiteSpace  = false;
		$document->recover             = true;
		$document->strictErrorChecking = false;
		$document->formatOutput        = false;
		$document->resolveExternals    = false;
		$document->validateOnParse     = false;
		$document->xmlStandalone       = true;

		return $document;
	}

	/**
	 * Resolve the absolute file path of a relative template path; paths inside
	 * plugins ("plugins/{plugin}/{path}") resolve to the plugin public folder.
	 *
	 * Absolute paths (starting with "/") are used as-is.
	 *
	 * @param string $template
	 */
	private function resolveTemplateFilePath(string $template): string {
		//absolute path as it is
		if ($template[0] === '/') {
			return $template;
		}

		if (str_starts_with($template, 'plugins/')) {
			$template   = str_replace('plugins/', '', $template);
			$p          = strpos($template, '/');
			$pluginName = substr($template, 0, $p);
			$nameSpace  = substr($template, $p + 1);
			$app        = '';

			if ($this->app !== 'app') {
				$app = $this->app . DS;
			}

			return DIR_PLUGINS . $pluginName . DS . 'public' . DS . $app . $nameSpace;
		}

		$view = $this->view;

		return $view->getTemplatePath() . $template;
	}

	/**
	 * Load the current content/template into the document, converting json
	 * templates through an xml representation.
	 *
	 * @param \DOMDocument $document
	 */
	private function loadTemplateDocument(\DOMDocument $document): void {
		$view = $this->view;
		libxml_use_internal_errors(true);
		$this->documentType = $view->getDocumentType();

		if ($this->content) {
			if ($this->documentType === 'html') {
				@$document->loadHTML($this->content);
			} else {
				@$document->loadXML($this->content);
			}

			return;
		}

		$template           = $this->view->template();
		$extension          = strtolower(trim(substr($template, -4), '.'));
		$this->documentType = $extension;
		$template           = $this->resolveTemplateFilePath($template);

		if ($this->documentType === 'html') {
			@$document->loadHTMLFile($template, LIBXML_NOWARNING | LIBXML_NOERROR);
		} else {
			$content = file_get_contents($template);

			if ($this->documentType === 'json') {
				//remove json comments from line start
				$content = removeJsonComments($content);
				$json    = json_decode($content, true);
				$json    = \Vvveb\prepareJson($json);
				$xml     = \Vvveb\array2xml($json);
				@$document->loadXML($xml);
			} else {
				@$document->loadXML($content, LIBXML_NOWARNING | LIBXML_NOERROR);
			}
		}
	}

	/**
	 * Expand data-v-copy-from / data-v-save-global attributes by pulling the
	 * referenced elements from external files into the document.
	 *
	 * @param \DOMXpath    $xpath
	 * @param \DOMDocument $document
	 */
	private function expandCopyFromElements(\DOMXPath $xpath, \DOMDocument $document): void {
		$view = $this->view;
		$i    = 0;

		//include froms in case any component_ is included
		while (($elements = $xpath->query('//*[ @data-v-copy-from or @data-v-save-global ]')) && $elements->length && $i++ < 2) {
			$fromDocument = $this->createDomDocument();

			foreach ($elements as $element) {
				$attribute = $element->getAttribute('data-v-copy-from') ?: $element->getAttribute('data-v-save-global');
				$element->removeAttribute('data-v-copy-from');
				$element->removeAttribute('data-v-save-global');

				if (preg_match('/([^\,]+)\,([^$,]+)/', $attribute, $from)) {
					$file     = html_entity_decode(trim($from[1]));
					$selector = html_entity_decode(trim($from[2]));

					if (isset($this->copyFromElements[$file][$selector])) {
						$fromElements = $this->copyFromElements[$file][$selector];
					} else {
						if ($this->documentType === 'html') {
							$fromDocument->loadHTMLFile($view->getTemplatePath() . $file, LIBXML_NOWARNING | LIBXML_NOERROR);
						} else {
							//load() reads from a file path, unlike loadXML() which expects the xml content
							@$fromDocument->load($view->getTemplatePath() . $file, LIBXML_NOWARNING | LIBXML_NOERROR);
						}

						$fromXpath = new \DOMXPath($fromDocument);

						$fromElements                             = $fromXpath->query(\Vvveb\cssToXpath($selector));
						$this->copyFromElements[$file][$selector] = $fromElements;
					}

					$count  = 0;
					$parent = $element->parentNode;

					foreach ($fromElements as $externalNode) {
						$importedNode = $document->importNode($externalNode, true);

						if ($parent) {
							if ($count) {
								$parent->appendChild($importedNode);
							} else {
								$parent->replaceChild($importedNode, $element);
							}
							$element = $importedNode;
							$count++;
						}
					}
				}
			}
		}
	}

	/**
	 * Extract options from data-v-* attributes of an element, validate them
	 * against the component class defaults and compute the instance hash.
	 *
	 * @param mixed $element
	 *
	 * @return array{component: string, options: array<array-key, mixed>}|null Null when the element must be skipped.
	 */
	private function parseComponentElement($element): ?array {
		$component = '';
		$opts      = [];

		foreach ($element->attributes as $attr) {
			$nodeName = $attr->nodeName;

			if (str_starts_with($nodeName, 'data-v-component-')) {
				$component = str_replace('data-v-component-', '', $nodeName);
			} elseif (str_starts_with($nodeName, 'data-v-')) {
				$option        = str_replace('data-v-', '', $nodeName);
				$opts[$option] = $attr->nodeValue;
			}
		}

		['class' => $componentClass, 'file' => $file] = $this->resolveComponentClass($component, true);

		if (!file_exists($file)) {
			if (defined('DEBUG') && \DEBUG) {
				error_log("Component does not exist $componentClass => $file");
			}

			return null;
		}

		include_once $file;

		//do not add design only components
		if (isset($componentClass::$designOnly) && $componentClass::$designOnly === true) {
			return null;
		}

		$validOptions = array_keys($componentClass::$defaultOptions);
		$options      = [];

		//save options
		foreach ($opts as $name => $option) {
			if (in_array($name, $validOptions) && $option !== null) {
				if ((isset($option[0]) && ($option[0] === '{' || $option[0] === '[')) || (str_contains($option, ','))) {
					$options[$name] = json_decode($option, true);
				} else {
					$options[$name] = $option;
				}
			}
		}

		$options['_hash'] = md5($component . serialize($options));

		return ['component' => $component, 'options' => $options];
	}

	/**
	 * Parse the template file (HTML/XML/JSON) and extract component definitions from data-v-component-* attributes.
	 *
	 * @return void
	 */
	public function generateRequiredComponents(): void {
		$document = $this->createDomDocument();

		$this->loadTemplateDocument($document);

		$xpath = new \DOMXPath($document);

		$this->expandCopyFromElements($xpath, $document);

		//search for elements that have an attribute starting with data-v-component-
		$elements   = $xpath->query('//*[@*[starts-with(name(), "data-v-component-")]]');
		$components = [];

		foreach ($elements as $element) {
			$parsed = $this->parseComponentElement($element);

			if ($parsed !== null) {
				$components[$parsed['component']][] = $parsed['options'];
			}
		}

		if ($components) {
			$this->components = $components;
		}
	}

	/**
	 * Persist the parsed component definitions to a PHP cache file.
	 *
	 * @return int|false Number of bytes written, or false on failure.
	 */
	public function saveTemplateComponents(): int|false {
		$php = var_export($this->components, true);

		return file_put_contents($this->componentsFile, '<?php return ' . $php . ';');
	}

	/**
	 * Load previously cached component definitions from the PHP cache file.
	 *
	 * @return bool True when definitions were loaded.
	 */
	public function loadTemplateComponents(): bool {
		if (is_file($this->componentsFile)) {
			$components = include $this->componentsFile;

			if (is_array($components)) {
				$this->components = $components;

				return true;
			}
		}

		return false;
	}
}
