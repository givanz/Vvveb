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
use function Vvveb\addTranslationDomain;
use function Vvveb\config;
use function Vvveb\isEditor;
use Vvveb\System\Component\Component;
use Vvveb\System\Event;
use Vvveb\System\Sites;

include DIR_SYSTEM . 'vtpl' . DS . 'vtpl.php';

#[\AllowDynamicProperties]
class View {
	static private $instance;

	private $templatePath;

	private $theme;

	private $htmlPath;

	private $template;

	private $tplFile;

	private $templateEngine;

	private $compiledTemplate;

	private $serviceTemplate;

	private $component;

	private $componentCount;

	private $componentContent;

	private $html;

	private $useComponent;

	private $isEditor = false;

	private $_type = 'html';

	function getTemplateEngineInstance() {
		if (! $this->templateEngine) {
			self :: getInstance();
		}

		return $this->templateEngine;
	}

	function __construct($app = null) {
		//$this->theme        = \Vvveb\config($this->app . '.theme', 'default');
		$this->app    = $app ?? APP;
		$relativePath = PUBLIC_PATH . 'themes/';

		if ($this->app == 'app') {
			$this->theme        = Sites::getTheme() ?? 'default';
			$themePath = DIR_ROOT . join(DS, ['public', 'themes']) . DS . $this->theme . DS;
		} else {
			$this->theme  = config($this->app . '.theme', 'default');
			$relativePath = PUBLIC_PATH . $this->app . '/';
			$themePath = DIR_ROOT . DS . 'public' . DS . $this->app . DS . $this->theme . DS;
		}

		if (isEditor()) {
			$this->isEditor = true;
		}

		/*
		$domain = $this->theme . '-domain';
		addTranslationDomain($domain);
		 */

		$this->htmlPath     = $themePath;
		$this->templatePath = $themePath; //\Vvveb\config($this->app . '.theme', 'default') . DS;

		if (isset($_REQUEST['_component_ajax']) && $this->isEditor) {
			$this->component        = \Vvveb\filter('/[a-z\-]+/', $_REQUEST['_component_ajax'], 80);
			$this->componentCount   = \Vvveb\filter('/\d+/', $_REQUEST['_component_id'],  4);
			//$this->componentCount   = 0;
			//if (isset($_REQUEST['_server_template'])) {
			$this->componentContent = $_POST['_component_content'] ?? '';
			$this->html             = $_POST['html'] ?? '';
			//}
		}

		$selector = $count = null;

		if (isset($this->component)) {
			$selector = '[data-v-component-' . $this->component . ']';
		}

		$templateEngine = 'Vtpl';

		$template = new $templateEngine($selector, $this->componentCount, $this->componentContent);

		$template->addTemplatePath(DIR_ROOT . APP . DS . 'template' . DS);
		$template->setHtmlPath($this->htmlPath);

		if ($this->isEditor) {
			$this->isEditor = true;
			$template->removeVattrs(false);
		} else {
			$template->setRelativePath($relativePath . $this->theme . '/');
		}

		$this->templateEngine = $template;
	}

	static function getInstance() {
		if (self :: $instance === NULL) {
			return self :: $instance = new self(); //create class instance
		}

		return self :: $instance;
	}

	function removeVattrs($flag = true) {
		return $this->templateEngine->removeVattrs($flag);
	}

	function setTheme($theme = false) {
		$relativePath = PUBLIC_PATH . 'themes/';

		if (! $theme) {
			if ($this->app == 'app') {
				$this->theme = Sites::getTheme() ?? 'default';
				$themePath = DIR_ROOT . join(DS, ['public', 'themes']) . DS . $this->theme . DS;
			} else {
				$this->theme  = config($this->app . '.theme', 'default');
				$relativePath = PUBLIC_PATH . $this->app . '/';
				$themePath = DIR_ROOT . DS . 'public' . DS . $this->app . DS . $this->theme . DS;
			}
		} else {
			$theme       = \Vvveb\filter('/[a-z0-9_-]*/', $theme, 30);
			$themePath   = DIR_ROOT . join(DS, ['public', 'themes']) . DS . $this->theme . DS;
			$this->theme = $theme;
		}

		$this->htmlPath       = $themePath;
		$this->templatePath   = $themePath;
		$this->templateEngine->setHtmlPath($this->htmlPath);

		if (! $this->isEditor) {
			$this->templateEngine->setRelativePath($relativePath . $this->theme . '/');
		}
	}

	function getTheme() {
		return $this->theme;
	}

	function getDocumentType() {
		return $this->templateEngine->getDocumentType();
	}

	function getTemplatePath() {
		return $this->templatePath;
	}

	function compiledTemplate() {
		return $this->compiledTemplate;
	}

	function set($data) {
		if ($data && is_array($data)) {
			foreach ($data as $key => &$value) {
				$this->$key = $value;
			}
		}
	}

	function serviceTemplate() {
		return $this->serviceTemplate;
	}

	function checkNeedRecompile($service = false) {
		if (! $this->template) {
			return false;
		}

		$this->template    = str_replace(['/', '\\'], DS, $this->template);
		$templatePath      = $this->templatePath;
		$template          = $this->template;
		$templateMtime     = null;
		$templateFile      = $templatePath . $template;
		$html              = DIR_ROOT . APP . DS . 'template' . DS . $this->tplFile;

		//absolute path
		if ($this->template[0] == '/') {
			$templatePath = dirname($this->template) . '/';
			$template     = basename($this->template);
			$templateFile = $this->template;
		} else {
			if (strpos($this->template, 'plugins' . DS) === 0) {
				$templatePath = DIR_PUBLIC . 'plugins' . DS;
				//$templatePath = $this->templatePath = DIR_PUBLIC . 'plugins' . DS;

				$template   = str_replace('plugins' . DS, '', $template);
				$p          = strpos($template, DS);
				$pluginName = substr($template, 0, $p);
				$nameSpace  = substr($template, $p + 1);

				if ($this->app == 'admin') {
					$tpl      = $pluginName . DS . join(DS, [$this->app, 'template']) . DS . $nameSpace;
					$template = $pluginName . DS . $this->app . DS . $nameSpace;
				} else {
					$tpl      = $pluginName . DS . join(DS, [$this->app, 'template']) . DS . $nameSpace;
					$template = $pluginName . DS . $nameSpace;
				}

				$this->tplFile      = str_replace(['.html', '.xml', '.json'], '.tpl', $tpl);
				$html               = DIR_PLUGINS . $this->tplFile;
				$templateFile       = $templatePath . $template;
			}
		}

		if (! file_exists($templatePath . $template)) {
			if ($template == 'error404.html' || $template == 'error500.html') {
				//if theme is missing error page then use the default
				$templateFile = DIR_PUBLIC . $template;
			} else {
				FrontController::notFound(true, [
					'message' => __('Html template not found!'),
					'file'    => $templatePath . $template,
				]);
			}
		}

		//$this->tplFile = $templateFile;
		$this->templatePath = $templatePath;

		if (file_exists($templateFile)) {
			$templateMtime = filemtime($templateFile);
		}

		$htmlMtime = 0;

		if (file_exists($html)) {
			$htmlMtime = filemtime($html);
		}

		$compiledMtime = 0;

		if (file_exists($this->compiledTemplate)) {
			$compiledMtime = @filemtime($this->compiledTemplate);
		}

		if ((max($templateMtime, $htmlMtime) > $compiledMtime
			 || ! file_exists($this->compiledTemplate))
			/*|| (defined('DEBUG') && DEBUG) */ || isset($_POST['_component_content'])) {
			$this->compile($templateFile, $this->compiledTemplate, $service);
		}
	}

	private function compile($filename, $file, $service = false) {
		//@touch($file); //if recompiling takes longer avoid avoid other recompile requests
		//regenerate component file
		if ($this->useComponent && ! defined('CLI')) {
			//regenerate components cache
			if (! $service) {
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

		list($this->template, $filename, $this->tplFile) =
		Event :: trigger(__CLASS__,__FUNCTION__, $this->template, $filename, $this->tplFile, $this->templateEngine, $this);

		if ($this->html) {
			$errors = $this->templateEngine->loadHtml($this->html);
		} else {
			$errors = $this->templateEngine->loadHtmlTemplate($filename);
		}

		//if no template defined use the default
		if ($this->tplFile[0] == '/') {
			$this->tplFile = DIR_ROOT . APP . DS . 'template' . DS . 'common.tpl';
			$this->templateEngine->loadTemplateFile($this->tplFile);
		} else {
			if (strpos($this->template, 'plugins' . DS) === 0) {
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
				if (! file_exists(DIR_ROOT . APP . DS . 'template' . DS . $this->tplFile)) {
					$this->tplFile = 'common.tpl';
				}
				$this->templateEngine->loadTemplateFileFromPath($this->tplFile);
			}
		}

		list($this->template, $filename, $this->tplFile) =
		Event :: trigger(__CLASS__,__FUNCTION__ . ':after', $this->template, $filename, $this->tplFile, $this->templateEngine, $this);
		$this->templateEngine->saveCompiledTemplate($file);
	}

	function tplFile($filename) {
		$this->tplFile = $filename;
	}

	function template($filename = null) {
		if ($filename === false) {
			$this->template = false;

			return;
		}

		if ($filename) {
			$filename = str_replace('..', '', $filename);

			$compiledFilename = DIR_COMPILED_TEMPLATES
			. $this->app . '_' . (defined('SITE_ID') ? SITE_ID : '-') . '_'
			. ((is_null($this->component)) ? '' : $this->component . $this->componentCount . '_')
			. $this->theme . '_'
			. str_replace([DS, '/', '\\'] , '_', $filename)
			. ($this->isEditor ? '-edit' : '');

			list($filename, $compiledFilename) = Event :: trigger(__CLASS__,__FUNCTION__, $filename, $compiledFilename, $this);

			$this->compiledTemplate        = $compiledFilename;
			$this->serviceTemplate         = $compiledFilename;
			$this->tplFile                 = str_replace(['.html', '.xml', '.json'], '.tpl', $filename);
			$this->template                = $filename;
		}

		return $this->template;
	}

	function fragment($selector, $index = 0) {
		$this->component      = $selector;
		$this->componentCount = $index;
	}

	function setType($type) {
		$this->_type = $type;
	}

	function getType() {
		return $this->_type;
	}

	function render($useComponent = true, $output = true, $service = false) {
		Event :: trigger(__CLASS__,__FUNCTION__, $this->template, $this->tplFile, $this->templateEngine, $this);

		$this->useComponent = $useComponent;

		if ($useComponent && ! defined('CLI')) {
			if ($service) {
				$service = new Component($this, $this->componentContent ? true : false, $this->componentContent);
			} else {
				$service = Component::getInstance($this, $this->componentContent ? true : false, $this->componentContent);
			}
		}

		/*
		if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest' && ! isset($_REQUEST['_component_ajax'])) {
			$this->_type = 'json';
		}*/

		if ($this->_type == 'text') {
			if (isset($this->text)) {
				if ($output) {
					echo $this->text;
				} else {
					return $this->text;
				}
			}

			return;
		}

		if ($this->_type == 'json') {
			$jsonFlags = 0;

			//if (defined('CLI')) {
			$jsonFlags = JSON_PRETTY_PRINT;
			//}
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
			if (! $this->template) {
				self::template();
			}
			$this->checkNeedRecompile($service);

			if ($this->compiledTemplate) {
				if (! file_exists($this->compiledTemplate)) {
					return FrontController::notFound();
				}

				try {
					if ($output) {
						include_once $this->compiledTemplate;
					} else {
						ob_start();

						include_once $this->compiledTemplate;
						$return = ob_get_contents();
						ob_end_clean();

						return $return;
					}
				} catch (\ParseError | \Error $e) {
					$data = \Vvveb\System\Core\exceptionToArray($e, $this->compiledTemplate);

					return \Vvveb\System\Core\FrontController :: notFound(false, $data, 500);
				}
			}
		}
	}
}
