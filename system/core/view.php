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

use function Vvveb\__;
use function Vvveb\config;
use function Vvveb\isEditor;
use Vvveb\System\Component\Component;
use Vvveb\System\Event;
use Vvveb\System\Sites;

include DIR_SYSTEM . 'vtpl' . DS . 'vtpl.php';

#[\AllowDynamicProperties]
class View {
	/** @var self|null */
	private static ?self $instance = null;

	/** @var string|null */
	private ?string $templatePath = null;

	/** @var string|null */
	private ?string $theme = null;

	/** @var string|null */
	private ?string $htmlPath = null;

	/** @var string|false|null */
	private string|false|null $template = null;

	/** @var string|null */
	private ?string $tplFile = null;

	/** @var object|null Template engine instance (Vtpl) */
	private ?object $templateEngine = null;

	/** @var string|null */
	private ?string $compiledTemplate = null;

	/** @var string|null */
	private ?string $serviceTemplate = null;

	/** @var string|null */
	private ?string $component = null;

	/** @var string|null */
	private string|int|null $componentCount = null;

	/** @var string|null */
	private ?string $componentContent = null;

	/** @var string|null */
	private ?string $html = null;

	/** @var bool|null */
	private ?bool $useComponent = null;

	private bool $isEditor = false;

	private string $_type = 'html';

	/**
	 * Get the template engine instance, creating it if needed.
	 *
	 * @return object|null Template engine instance (Vtpl)
	 */
	public function getTemplateEngineInstance(): ?object {
		if (!$this->templateEngine) {
			self :: getInstance();
		}

		return $this->templateEngine;
	}

	/**
	 * Resolve theme name, theme path and relative path for an app.
	 *
	 * Shared by the constructor and setTheme(): false selects the default
	 * theme for the app, a string selects (and sanitizes) that theme.
	 *
	 * @param string|false $theme Theme name or false for the app default
	 * @param string       $app   App name (app, admin, ...)
	 *
	 * @return array{0: string, 1: string, 2: string} theme, theme path, relative path
	 */
	private function resolveThemePaths(string|false $theme, string $app): array {
		$relativePath = PUBLIC_PATH . 'themes/';

		if (!$theme) {
			if ($app === 'app') {
				$theme     = Sites::getTheme(SITE_ID);
				$themePath = DIR_ROOT . implode(DS, ['public', 'themes']) . DS . $theme . DS;
			} else {
				$theme        = config($app . '.theme', 'default');
				$relativePath = PUBLIC_PATH . $app . '/';
				$themePath    = DIR_ROOT . DS . 'public' . DS . $app . DS . $theme . DS;
			}
		} else {
			$theme     = \Vvveb\filter('/[a-z0-9_-]*/', $theme, 30);
			$themePath = DIR_ROOT . implode(DS, ['public', 'themes']) . DS . $theme . DS;
		}

		return [$theme, $themePath, $relativePath];
	}

	/**
	 * @param string|null $app
	 */
	public function __construct(?string $app = null) {
		$this->app    = $app ?? APP;

		[$this->theme, $themePath, $relativePath] = $this->resolveThemePaths(false, $this->app);

		$this->isEditor = isEditor();

		$this->htmlPath     = $themePath;
		$this->templatePath = $themePath;

		if (isset($_REQUEST['_component_ajax']) && $this->isEditor) {
			//check if user key is correct to allow override for editor
			if (($admin = \Vvveb\System\User\Admin :: current()) && ($userKey = $_POST['user_key'] ?? '') && ($adminId = $_POST['admin_id'] ?? '')) {
				$correctKey   = $adminId . '-' . SITE_ID;
				$key          = \Vvveb\getConfig('app.key');
				$decriptedKey = \Vvveb\decrypt($key, $userKey);

				if (($admin['admin_id'] != $adminId) || $decriptedKey != $correctKey) {
					exit('Invalid user key!');
				}

				$this->component        = \Vvveb\filter('/[a-z\-]+/', $_REQUEST['_component_ajax'], 80);
				$this->componentCount   = \Vvveb\filter('/\d+/', $_REQUEST['_component_id'], 4);

				$this->componentContent = $_POST['_component_content'] ?? '';
				$this->html             = $_POST['html'] ?? '';
			} else {
				exit('Invalid request!');
			}
		}

		$selector = null;

		if (isset($this->component)) {
			$selector = '[data-v-component-' . $this->component . ']';
		}

		$templateEngine = 'Vtpl';

		$template = new $templateEngine($selector, $this->componentCount, $this->componentContent);

		$template->addTemplatePath(DIR_ROOT . APP . DS . 'template' . DS);
		$template->setHtmlPath($this->htmlPath);

		if ($this->isEditor) {
			$template->removeVattrs(false);
			$template->setMinifyHtml(false);
		} else {
			$template->removeVattrs(false);
			//$template->setMinifyHtml(true);
			$template->setRelativePath($relativePath . $this->theme . '/');
		}

		$this->templateEngine = $template;
	}

	/**
	 * Get singleton instance of View.
	 */
	public static function getInstance(): self {
		if (self :: $instance === null) {
			return self :: $instance = new self(); //create class instance
		}

		return self :: $instance;
	}

	/**
	 * Remove v-attributes from HTML output.
	 *
	 * @param bool $flag
	 *
	 * @return mixed
	 */
	public function removeVattrs(bool $flag = true): mixed {
		return $this->templateEngine->removeVattrs($flag);
	}

	/**
	 * Set the active theme.
	 *
	 * @param string|false $theme Theme name or false to use default
	 *
	 * @return void
	 */
	public function setTheme(string|false $theme = false): void {
		[$this->theme, $themePath, $relativePath] = $this->resolveThemePaths($theme, $this->app);

		$this->htmlPath       = $themePath;
		$this->templatePath   = $themePath;
		$this->templateEngine->setHtmlPath($this->htmlPath);

		if (!$this->isEditor) {
			$this->templateEngine->setRelativePath($relativePath . $this->theme . '/');
		}
	}

	/**
	 * Get the current theme name.
	 */
	public function getTheme(): string {
		return $this->theme;
	}

	/**
	 * Get the document type from the template engine.
	 */
	public function getDocumentType(): string {
		return $this->templateEngine->getDocumentType();
	}

	/**
	 * Get the template path.
	 */
	public function getTemplatePath(): string {
		return $this->templatePath;
	}

	/**
	 * Get the compiled template path.
	 */
	public function compiledTemplate(): ?string {
		return $this->compiledTemplate;
	}

	/**
	 * Set dynamic properties from an associative array.
	 *
	 * @param array<string, mixed> $data
	 *
	 * @return void
	 */
	public function set(array $data): void {
		foreach ($data as $key => $value) {
			$this->$key = $value;
		}
	}

	/**
	 * Get the service template path.
	 */
	public function serviceTemplate(): ?string {
		return $this->serviceTemplate;
	}

	/**
	 * Check if the template needs recompilation and recompile if necessary.
	 *
	 * @param object|null $service
	 *
	 * @return void
	 */
	public function checkNeedRecompile(?object $service = null): void {
		if (!$this->template) {
			return;
		}

		$this->template    = str_replace(['/', '\\'], DS, $this->template);
		$templatePath      = $this->templatePath;
		$template          = $this->template;
		$templateMtime     = null;
		$templateFile      = $templatePath . $template;
		$tpl               = DIR_ROOT . APP . DS . 'template' . DS . $this->tplFile;

		//absolute path
		if ($this->template[0] === DS) {
			$templatePath = dirname($this->template) . DS;
			$template     = basename($this->template);
			$templateFile = $this->template;
		} else {
			if (str_starts_with($this->template, 'plugins' . DS)) {
				$templatePath = DIR_PUBLIC . 'plugins' . DS;
				//$templatePath = $this->templatePath = DIR_PUBLIC . 'plugins' . DS;

				$template   = str_replace('plugins' . DS, '', $template);
				$p          = strpos($template, DS);
				$pluginName = substr($template, 0, $p);
				$nameSpace  = substr($template, $p + 1);

				if ($this->app === 'admin') {
					$template = $pluginName . DS . $this->app . DS . $nameSpace;
				} else {
					$template = $pluginName . DS . $nameSpace;
				}

				$tpl = $pluginName . DS . implode(DS, [$this->app, 'template']) . DS . $nameSpace;

				$this->tplFile      = str_replace(['.html', '.xml', '.json'], '.tpl', $tpl);
				$tpl                = DIR_PLUGINS . $this->tplFile;
				$templateFile       = $templatePath . $template;
			}
		}

		if (!file_exists($templatePath . $template)) {
			if ($this->fallback) {
				$template     = $this->fallback;
				$templateFile = $templatePath . $template;
			} else {
				if ($template === 'error404.html' || $template === 'error500.html') {
					//if theme is missing error page then use the default
					$templateFile = DIR_PUBLIC . $template;
				} else {
					FrontController::notFound(true, [
						'message' => __('Html template not found!'),
						'file'    => $templatePath . $template,
					]);
				}
			}
		}

		$this->templatePath = $templatePath;

		if (file_exists($templateFile)) {
			$templateMtime = filemtime($templateFile);
		}

		$tplMtime = 0;

		if (file_exists($tpl)) {
			$tplMtime = filemtime($tpl);
		}

		$compiledMtime = 0;

		if (file_exists($this->compiledTemplate)) {
			$compiledMtime = @filemtime($this->compiledTemplate);
		}

		if ((max($templateMtime, $tplMtime) > $compiledMtime
			 || !file_exists($this->compiledTemplate))
			/*|| (defined('DEBUG') && DEBUG) */ || isset($_POST['_component_content'])) {
			$this->compile($templateFile, $this->compiledTemplate, $service);
		}
	}

	/**
	 * Compile the template file.
	 *
	 * @param string  $filename
	 * @param string  $file
	 * @param ?object $service
	 *
	 * @return void
	 */
	private function compile(string $filename, string $file, ?object $service = null): void {
		//regenerate component file
		if ($this->useComponent && !defined('CLI')) {
			//regenerate components cache
			if (!$service) {
				if ($this->html) {
					$html = $this->html;
				} else {
					$html = $this->componentContent;
				}

				$service = Component::getInstance($this, true, $html);
			}
		}

		if (is_dir($this->htmlPath . 'template')) {
			$this->templateEngine->addTemplatePath($this->htmlPath . 'template' . DS);
		}

		[$this->template, $filename, $this->tplFile] =
		Event :: trigger(__CLASS__, __FUNCTION__, $this->template, $filename, $this->tplFile, $this->templateEngine, $this);

		if ($this->html) {
			$errors = $this->templateEngine->loadHtml($this->html);
		} else {
			$errors = $this->templateEngine->loadHtmlTemplate($filename);
		}

		//if no template defined use the default
		if ($this->tplFile[0] === DS) {
			$this->tplFile = DIR_ROOT . APP . DS . 'template' . DS . 'common.tpl';
			$this->templateEngine->loadTemplateFile($this->tplFile);
		} else {
			if (str_starts_with($this->template, 'plugins' . DS)) {
				//$template   = str_replace('plugins' . DS, '', $this->template);
				$template   = $this->tplFile;
				$p          = strpos($template, DS);
				$pluginName = substr($template, 0, $p);
				$nameSpace  = substr($template, $p + 1);

				if (file_exists(DIR_PLUGINS . $this->tplFile)) {
					$this->tplFile = DIR_PLUGINS . $this->tplFile;
				} else {
					$this->tplFile = DIR_ROOT . APP . DS . 'template' . DS . 'common.tpl';
				}

				//$this->tplFile = $pluginName . DS . $this->app . DS . 'template' . DS . $nameSpace;
				$this->templateEngine->loadTemplateFile($this->tplFile);
				/*
				if ($this->app == 'admin') {
					$this->tplFile = $pluginName . DS . $this->app ."/template/$nameSpace";
				} else {
					$this->tplFile = "$pluginName/admin/template/$nameSpace";
				}*/
			} else {
				if (!file_exists(DIR_ROOT . APP . DS . 'template' . DS . $this->tplFile)) {
					$this->tplFile = 'common.tpl';
				}
				$this->templateEngine->loadTemplateFileFromPath($this->tplFile);
			}
		}

		[$this->template, $filename, $this->tplFile] =
		Event :: trigger(__CLASS__, __FUNCTION__ . ':after', $this->template, $filename, $this->tplFile, $this->templateEngine, $this);
		$this->templateEngine->saveCompiledTemplate($file);
	}

	/**
	 * Set the template file name.
	 *
	 * @param string $filename
	 *
	 * @return void
	 */
	public function tplFile(string $filename): void {
		$this->tplFile = $filename;
	}

	/**
	 * Get or set the template filename.
	 *
	 * @param string|false|null $filename Template filename, false to clear, or null to get current
	 * @param string|null|null  $fallback
	 *
	 * @return string|false|null Current template filename
	 */
	public function template(string|false|null $filename = null, ?string $fallback = null): string|false|null {
		if ($filename === false) {
			$this->template = false;

			return null;
		}

		if ($filename) {
			$filename = str_replace('..', '', $filename);

			$compiledFilename = DIR_COMPILED_TEMPLATES
			. $this->app . '_' . (defined('SITE_ID') ? SITE_ID : '-') . '_'
			. ((is_null($this->component)) ? '' : $this->component . $this->componentCount . '_')
			. $this->theme . '_'
			. str_replace([DS, '/', '\\'], '_', $filename)
			. ($this->isEditor ? '-edit' : '');

			[$filename, $compiledFilename] = Event :: trigger(__CLASS__, __FUNCTION__, $filename, $compiledFilename, $this);

			$this->compiledTemplate        = $compiledFilename;
			$this->serviceTemplate         = $compiledFilename;
			$this->tplFile                 = str_replace(['.html', '.xml', '.json'], '.tpl', $filename);
			$this->template                = $filename;
		}

		$this->fallback = $fallback;

		return $this->template;
	}

	/**
	 * Set the component selector and index for fragment rendering.
	 *
	 * @param string $selector CSS selector
	 * @param int    $index    Component index
	 *
	 * @return void
	 */
	public function fragment(string $selector, string|int $index = 0): void {
		$this->component      = $selector;
		$this->componentCount = $index;
	}

	/**
	 * Set the output type (html, json, text).
	 *
	 * @param string $type
	 *
	 * @return void
	 */
	public function setType(string $type): void {
		$this->_type = $type;
	}

	/**
	 * Get the current output type.
	 */
	public function getType(): string {
		return $this->_type;
	}

	/**
	 * Render the template.
	 *
	 * @param bool $useComponent Whether to use component rendering
	 * @param bool $output       Whether to echo output or return it
	 * @param bool $service      Whether this is a service template render
	 *
	 * @return mixed|string|null Rendered output string or void if outputting directly
	 */
	public function render(bool $useComponent = true, bool $output = true, bool $service = false): mixed {
		Event :: trigger(__CLASS__, __FUNCTION__, $this->template, $this->tplFile, $this->templateEngine, $this);

		$this->useComponent = $useComponent;

		if ($useComponent && !defined('CLI')) {
			if ($service) {
				$service = new Component($this, $this->componentContent ? true : false, $this->componentContent);
			} else {
				$service = Component::getInstance($this, $this->componentContent ? true : false, $this->componentContent);
			}
		}

		if ($this->_type === 'text') {
			if (!isset($this->text)) {
				return null;
			}

			if ($output) {
				echo $this->text;

				return null;
			}

			return $this->text;
		}

		if ($this->_type === 'json') {
			$jsonFlags = JSON_PRETTY_PRINT;
			ob_start();

			if (isset($this->json)) {
				$json = json_encode($this->json, $jsonFlags);
			} else {
				$json = json_encode($this, $jsonFlags);
			}

			if ($output) {
				echo $json;
			} else {
				return $json;
			}
		} else { //html
			if (!$this->template) {
				self::template();
			}
			$this->checkNeedRecompile($service);

			if ($this->compiledTemplate) {
				if (!file_exists($this->compiledTemplate)) {
					return FrontController::notFound(true, [
						'message' => __('Compiled template not found!'),
						'file'    => $this->template,
					]);
				}

				try {
					if ($output) {
						include $this->compiledTemplate;

						return '';
					} else {
						ob_start();

						include $this->compiledTemplate;
						$return = ob_get_contents();
						ob_end_clean();

						return $return;
					}
				} catch (\ParseError|\Error $e) {
					static $handled;

					if ($handled) {
						if (DEBUG) {
							echo $e->getMessage() . ' in ' . $e->getFile() . ' on line ' . $e->getLine();
						} else {
							exit('Server error, enable debug for more info');
						}

						return null;
					}
					$handled = true;

					$data = \Vvveb\System\Core\exceptionToArray($e, $this->compiledTemplate);
					FrontController :: notFound(false, $data, 500);
				}
			}
		}

		return '';
	}
}
