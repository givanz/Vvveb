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

namespace Vvveb\Controller\Editor;

use \Vvveb\Sql\menuSQL;
use function Vvveb\__;
use Vvveb\Controller\Base;
use function Vvveb\getUrl;
use function Vvveb\sanitizeFileName;
use function Vvveb\slugify;
use Vvveb\Sql\PostSQL;
use Vvveb\Sql\ProductSQL;
use Vvveb\System\CacheManager;
use Vvveb\System\Core\View;
use Vvveb\System\Event;
use Vvveb\System\Sites;

class Editor extends Base {
	protected $revisionDateFormat = 'Y-m-d_H:i:s';

	private $themeConfig = [];

	private $skipFolders = ['src', 'source', 'backup', 'import'];

	private $skipFiles = [];

	function init() {
		$this->loadThemeConfig();

		return parent::init();
	}

	function oEmbedProxy() {
		$url = $this->request->get['url'];

		if (! $url) {
			return;
		}
		$result = getUrl($url, false);

		$this->response->setType('json');
		$this->response->output($result);
	}

	function getThemeFolder() {
		$theme = $this->request->get['theme'] ?? Sites::getTheme() ?? 'default';

		return DIR_THEMES . DS . $theme;
	}

	function loadThemeConfig() {
		$config = $this->getThemeFolder() . DS . 'theme.php';

		if (file_exists($config)) {
			$this->themeConfig = include $config;
		} else {
			$this->themeConfig = [];
		}
	}

	function loadTemplateList() {
		$list = $this->themeConfig['pages'] ?? [];

		$pages       = $list + \Vvveb\getTemplateList();
		list($pages) = Event::trigger(__CLASS__, __FUNCTION__, $pages);

		return $pages;
	}

	function loadEditorData() {
		$data = [];

		//menu list
		$menuSql               = new \Vvveb\Sql\menuSQL();
		$results               = $menuSql->getMenusList($this->global);

		$data += $results;

		list($data) = Event::trigger(__CLASS__, __FUNCTION__, $data);

		return $data;
	}

	/*
		Load theme sections, components and inputs
	 */
	function loadThemeAssets() {
		$themeFolder = $this->getThemeFolder();
		$view        = &$this->view;
		$themeJs 	   = [];

		foreach (['inputs', 'components', 'blocks', 'sections'] as $type) {
			$$type = [];
			$glob  = glob("$themeFolder/$type/*.js");

			foreach ($glob as &$file) {
				$base          = str_replace('.js', '', basename($file));
				$$type[$base]  = str_replace($themeFolder, $view->themeBaseUrl, $file);
			}
		}

		list($inputs, $components, $blocks, $sections) =
		Event::trigger(__CLASS__, __FUNCTION__, $inputs, $components, $blocks, $sections);

		$vvvebJs = '/js/vvvebjs.js';

		if (file_exists($themeFolder . $vvvebJs)) {
			$themeJs['vvvebjs'] = $view->themeBaseUrl . $vvvebJs;
		}

		$view->themeInputs     = $inputs;
		$view->themeComponents = $components;
		$view->themeSections   = $sections;
		$view->themeBlocks     = $blocks;
		$view->themeJs         = $themeJs;
	}

	function index() {
		$view               = View::getInstance();
		$view->themeBaseUrl = PUBLIC_PATH . 'themes/' . (Sites::getTheme() ?? 'default') . '/';
		$view->pages        = $this->loadTemplateList();

		$this->loadThemeAssets();

		if (isset($this->request->get['url'])) {
			$name     = $url      = $this->request->get['url'];
			$template = $this->request->get['template'] ?? \Vvveb\getUrlTemplate($url) ?? 'index.html';
			$folder 	 = $this->request->get['folder'] ?? false;
			$filename = $template;
			$file     = $template;
			$title    = \Vvveb\humanReadable(str_replace('.html', '', $url));

			if ($url == '/') {
				$title = __('Homepage');
				$name  = 'homepage-live';
			}

			$current_page = ['name' => $name, 'file' => $file, 'url' => $url, 'title' => $title, 'folder' => '', 'className' => 'page'];
			$view->pages  = [$name => $current_page] + $view->pages;
		}

		$admin_path                    = \Vvveb\adminPath();
		$mediaControllerPath           = $admin_path . 'index.php?module=media/media';
		$controllerPath                = $admin_path . 'index.php?module=editor/editor';
		$revisionsPath                 = $admin_path . 'index.php?module=editor/revisions';

		$this->view->scanUrl           = "$mediaControllerPath&action=scan";
		$this->view->uploadUrl         = "$mediaControllerPath&action=upload";
		$this->view->saveUrl           = "$controllerPath&action=save";
		$this->view->deleteUrl         = "$controllerPath&action=delete";
		$this->view->renameUrl         = "$controllerPath&action=rename";
		$this->view->saveReusableUrl   = "$controllerPath&action=saveReusable";
		$this->view->oEmbedProxyUrl    = "$controllerPath&action=oEmbedProxy";

		$this->view->revisionsUrl      = "$revisionsPath&action=revisions";
		$this->view->revisionLoadUrl   = "$revisionsPath&action=revisionLoad";
		$this->view->revisionDeleteUrl = "$revisionsPath&action=revisionDelete";

		$view->templates               = \Vvveb\getTemplateList();
		$view->folders                 = \Vvveb\getThemeFolderList();
		$view->data                    = $this->loadEditorData();
	}

	function getComponent($html, $options) {
	}

	function backup($page) {
		$themeFolder  = $this->getThemeFolder() . DS;
		$backupFolder = $themeFolder . 'backup' . DS;
		$page         = str_replace('.html', '', sanitizeFileName($page));
		$backupName   =  str_replace(DS, '-', $page) . '|' . date($this->revisionDateFormat) . '.html';
		$file         = $themeFolder . $page . '.html';

		if (is_dir($backupFolder)) {
			if (file_exists($file)) {
				$content = file_get_contents($themeFolder . $page . '.html');

				return file_put_contents($backupFolder . $backupName, $content);
			}
		}

		return false;
	}

	function saveElements($elements) {
		$products   = new ProductSQL();
		$posts      = new PostSQL();
		$components = [];

		foreach ($elements as $element) {
			$component = $element['component'];
			$type      = $element['type'];
			$id        = $element['id'];
			$fields    = $element['fields'];

			//todo: check and load components from plugins
			include_once DIR_ROOT . "app/component/$component.php";
			$componentName =  "\Vvveb\Component\\$component";

			if (! isset($components[$component])) {
				$components[$component] = new $componentName();
			}
			$components[$component]->editorSave($id, $fields, $type);
		}
		/*
		switch ($type) {
			case 'product':
				$product_content = [];

				foreach ($fields as $field) {
					$name  = $field['name'];
					$value = $field['value'];

					if ($name == 'name' || $name == 'content') {
						$product_content[$name] = $value;
					} else {
						$product[$name] = $value;
					}
				}

				//$product_content['product_id'] = $id;
				$product_content['language_id'] = 1;

				$product['product_content'][]     = $product_content;
				$result                           = $products->edit(['product' => $product, 'product_id' => $id]);

			break;

			case 'post':
				$post_content = [];

				foreach ($fields as $field) {
					$name  = $field['name'];
					$value = $field['value'];

					if ($name == 'name' || $name == 'content') {
						$post_content[$name] = $value;
					} else {
						$post[$name] = $value;
					}
				}
				//$post['post_content']['post_id'] = $id;
				$post_content['language_id'] = 1;
				$post['post_content'][]      = $post_content;

				$result = $posts->edit(['post' => $post, 'post_id' => $id]);

			break;
		} */

		return true;
	}

	function delete() {
		$file        = sanitizeFileName($this->request->post['file']);
		$themeFolder = $this->getThemeFolder();
		//echo $themeFolder . DS . $file;

		if (unlink($themeFolder . DS . $file)) {
			$message = ['success' => true, 'message' => __('File deleted!')];
		} else {
			$message = ['success' => false, 'message' => __('Error deleting file!')];
		}

		$this->response->setType('json');
		$this->response->output($message);
	}

	function rename() {
		$file        = sanitizeFileName($this->request->post['file']);
		$newfile     = sanitizeFileName($this->request->post['newfile']);
		$duplicate   =  $this->request->post['duplicate'] ?? false;
		$themeFolder = $this->getThemeFolder();

		$currentFile = $themeFolder . DS . $file;
		$targetFile  =  $themeFolder . DS . $newfile;

		if ($duplicate) {
			if (copy($currentFile, $targetFile)) {
				$message = ['success' => true, 'message' => __('File copied!')];
			} else {
				$message = ['success' => false, 'message' => __('Error copying file!')];
			}
		} else {
			if (rename($currentFile, $targetFile)) {
				$message = ['success' => true, 'message' => __('File renamed!')];
			} else {
				$message = ['success' => false, 'message' => __('Error renaming file!')];
			}
		}

		$this->response->setType('json');
		$this->response->output($message);
	}

	function saveReusable() {
		$name        = slugify(sanitizeFileName($this->request->post['name']));
		$type        = $this->request->post['type'];
		$html        = $this->request->post['html'];

		$themeFolder = $this->getThemeFolder();
		$folder      = $themeFolder . DS . $type . 's' . DS . 'reusable' . DS;
		$file        = "$name.html";

		@mkdir($folder);

		if (file_put_contents($folder . $file, $html)) {
			$message = ['success' => true, 'message' => __('Element saved!')];
		} else {
			$message = ['success' => false, 'message' => __('Error saving!')];
		}

		$this->response->setType('json');
		$this->response->output($message);
	}

	function save() {
		$file             = $this->request->post['file'] ?? '';
		$startTemplateUrl = $this->request->post['startTemplateUrl'] ?? '';
		$name             = $this->request->post['name'] ?? '';
		$content          = $this->request->post['content'] ?? 'Lorem ipsum';
		$type             =  $this->request->post['type'] ?? false;
		$addMenu          =  $this->request->post['add-menu'] ?? false;
		$menu_id          =  $this->request->post['menu_id'] ?? false;
		$url              = '';

		$file             = sanitizeFileName(slugify(str_replace('.html', '', $file))) . '.html';

		if ($type && $name) {
			$slug    = slugify($name);
			$success = false;

			switch ($type) {
				case 'page':
				case 'post':
					$file             = sanitizeFileName("content/$slug.html");
					$startTemplateUrl = "content/$type.html";
					$post             = new PostSQL();
					$result           = $post->add([
						'post' => [
							'template'     => $file,
							'type'         => $type,
							'image'        => 'posts/2.jpg', //'placeholder.svg'
							'post_content' => [[
								'slug'        => $slug,
								'name'        => $name,
								'content'     => $content,
								'language_id' => $this->global['language_id'],
							]],
						] + $this->global, ] + $this->global);

					if ($result['post']) {
						$success    = true;
						$route      = "content/{$type}/index";
						$url        = \Vvveb\url($route, ['slug'=> $slug]);
					}

				break;

				case 'product':
					$file             = sanitizeFileName("product/$slug.html");
					$startTemplateUrl = "product/$type.html";
					$price            =  $this->request->post['price'] ?? 0;
					$product          = new ProductSQL();
					$result           = $product->add([
						'product' => [
							'model'           => '',
							'image'           => 'posts/2.jpg', //'placeholder.svg'
							'status'          => 1, //active
							'template'        => $file,
							'price'           => $price,
							'product_content' => [[
								'slug'        => $slug,
								'name'        => $name,
								'name'        => $name,
								'content'     => $content,
								'language_id' => $this->global['language_id'],
							]],
						] + $this->global, ] + $this->global);

					if ($result['product']) {
						$success    = true;
						$route      = "product/{$type}/index";
						$url        = \Vvveb\url($route, ['slug'=> $slug]);
					}

				break;
			}
		}

		$content          = $this->request->post['html'] ?? false;
		$elements         = $this->request->post['elements'] ?? false;
		$setTemplate      = $this->request->post['setTemplate'] ?? false;

		$view         = View::getInstance();
		$view->noJson = true;
		$success      = false;
		$text      		 = '';

		$themeFolder = $this->getThemeFolder();
		$baseUrl     = '/themes/' . (Sites::getTheme() ?? 'default') . '/';

		if ($startTemplateUrl) {
			$content = file_get_contents($themeFolder . DS . $startTemplateUrl);
			$content = preg_replace('@<base href[^>]+>@', '<base href="' . $baseUrl . '">', $content);
		}

		if (! $url) {
			$url = "$baseUrl/$file";
		}

		$data = compact('file', 'name', 'url', 'startTemplateUrl');

		if ($elements) {
			if ($this->saveElements($elements)) {
				$success   = true;
				$text      = __('Elements saved!') . "\n";
			} else {
				$success   = false;
				$text      = __('Error saving elements!') . "\n";
			}
		}

		if (! $startTemplateUrl) {
			if ($this->backup($file)) {
			} else {
				$success   = false;
				$text .= __('Error saving backup!') . "\n";
			}
		}

		if ($addMenu && $menu_id) {
			$menuData = ['menu_item' => [
				'menu_id'           => $menu_id,
				'url'               => $url,
				'menu_item_content' => [[
					'name'         => $name,
					'slug'         => $slug,
					'language_id'  => $this->global['language_id'],
					'content'      => '',
				]],
			]] + $this->global;

			$menus   = new menuSQL();
			$results = $menus->addMenuItem($menuData);

			if ($results) {
				$text .= "\n" . __('Menu item added!');
			}
		}

		//if plugins template use public path
		if (substr_compare($file[0],'/plugins/', 0, 9) === 0) {
			$fileName = DIR_PUBLIC . DS . $file;
		} else {
			$fileName = $themeFolder . DS . $file;
		}

		if (file_put_contents($fileName, $content)) {
			$success = true;
			$text .= __('File saved!');
		}

		if (CacheManager::clearCompiledFiles('app') && CacheManager::delete()) {
		}

		$data += ['success' => $success, 'message' => $text];

		$this->response->setType('json');
		$this->response->output($data);
	}
}
