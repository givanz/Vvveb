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
use Vvveb\System\Cache;
use Vvveb\System\CacheManager;
use Vvveb\System\Core\View;
use Vvveb\System\Event;
use Vvveb\System\Sites;
use function Vvveb\url;

class Editor extends Base {
	use GlobalTrait;

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

	private function getTheme() {
		return $theme = sanitizeFileName($this->request->get['theme'] ?? Sites::getTheme() ?? 'default');
	}

	private function getThemeFolder() {
		return DIR_THEMES . $this->getTheme();
	}

	private function loadThemeConfig() {
		$config = $this->getThemeFolder() . DS . 'theme.php';

		if (file_exists($config)) {
			$this->themeConfig = include $config;
		} else {
			$this->themeConfig = [];
		}
	}

	private function loadTemplateList($theme = null) {
		$list = $this->themeConfig['pages'] ?? [];

		$pages = $list + Cache::getInstance()->cache(APP, 'template-list.' . $theme,
		function () use ($theme) {
			return \Vvveb\getTemplateList($theme);
		}, 604800);

		list($pages) = Event::trigger(__CLASS__, __FUNCTION__, $pages);

		return $pages;
	}

	private function clearTemplateListCache($theme = null) {
		return Cache::getInstance()->delete(APP, 'template-list.' . $theme);
	}

	private function loadEditorData() {
		$data = [];

		//menu list
		$menuSql = new \Vvveb\Sql\menuSQL();
		$results = $menuSql->getMenusList($this->global);

		$data += $results;

		list($data) = Event::trigger(__CLASS__, __FUNCTION__, $data);

		return $data;
	}

	/*
		Load theme sections, components and inputs
	 */
	private function loadThemeAssets() {
		$themeFolder = $this->getThemeFolder();
		$view        = &$this->view;
		$themeJs     = [];

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
		$theme              = $this->getTheme();
		$themeParam         = ($theme ? '&theme=' . $theme : '');
		$view               = View::getInstance();
		$view->themeBaseUrl = PUBLIC_PATH . 'themes/' . $theme . '/';
		$view->pages        = $this->loadTemplateList($theme);

		$this->loadThemeAssets();

		$this->posts   = new PostSQL();
		$options       = [
			'type'  => 'page',
			'limit' => 100,
		] + $this->global;

		$results = $this->posts->getAll($options);
		$posts   = [];

		foreach ($results['posts'] as $post) {
			$slug = $post['slug'];
			$url  = url('content/page/index',['slug' => $slug, 'post_id' => $post['post_id']]);

			$posts[$slug] = [
				'name'      => $slug,
				'file'      => $post['template'] ? $post['template'] : 'content/page.html',
				'url'       => $url . ($theme ? '?theme=' . $theme : ''),
				'title'     => $post['name'],
				'post_id'   => $post['post_id'],
				'folder'    => '',
				'className' => 'page',
			];
		}

		if ($posts) {
			$view->pages = $posts + $view->pages;
		}

		if (isset($this->request->get['url'])) {
			$name     = $url      = $this->request->get['url'];
			$template = $this->request->get['template'] ?? \Vvveb\getUrlTemplate($url) ?? 'index.html';
			$folder 	 = $this->request->get['folder'] ?? false;
			$filename = $template;
			$file     = $template;
			$title    = \Vvveb\humanReadable(str_replace('.html', '', $url));

			if ($url == '/') {
				$title = __('Homepage');
				$name  = 'index';
			}

			$current_page = [
				'name'      => $name,
				'file'      => $file,
				'url'       => $url . ($theme ? '?theme=' . $theme : ''),
				'title'     => $title,
				'folder'    => '',
				'className' => 'page',
			];

			$view->pages = [$name => $current_page] + $view->pages;
		}

		$admin_path                    = \Vvveb\adminPath();
		$mediaControllerPath           = $admin_path . 'index.php?module=media/media';
		$controllerPath                = $admin_path . 'index.php?module=editor/editor' . $themeParam;
		$revisionsPath                 = $admin_path . 'index.php?module=editor/revisions' . $themeParam;
		$reusablePath                  = $admin_path . 'index.php?module=editor/reusable' . $themeParam;

		//media endpoints
		$this->view->scanUrl           = "$mediaControllerPath&action=scan";
		$this->view->uploadUrl         = "$mediaControllerPath&action=upload";
		$this->view->deleteUrl         = "$mediaControllerPath&action=delete";
		$this->view->renameUrl         = "$mediaControllerPath&action=rename";

		//editor endpoints
		$this->view->saveUrl           = "$controllerPath&action=save";
		$this->view->deleteFileUrl     = "$controllerPath&action=delete";
		$this->view->renameFileUrl     = "$controllerPath&action=rename";
		$this->view->saveReusableUrl   = "$reusablePath&action=save";
		$this->view->oEmbedProxyUrl    = "$controllerPath&action=oEmbedProxy";

		$this->view->revisionsUrl      = "$revisionsPath&action=revisions";
		$this->view->revisionLoadUrl   = "$revisionsPath&action=load";
		$this->view->revisionDeleteUrl = "$revisionsPath&action=delete";

		$view->templates               = \Vvveb\getTemplateList($theme);
		$view->folders                 = \Vvveb\getThemeFolderList($theme);
		$view->data                    = $this->loadEditorData();
	}

	private function backup($page) {
		$themeFolder  = $this->getThemeFolder() . DS;
		$backupFolder = $themeFolder . 'backup' . DS;
		$page         = str_replace('.html', '', sanitizeFileName($page));
		$backupName   = str_replace(DS, '-', $page) . '@' . str_replace(':',';', date($this->revisionDateFormat)) . '.html';
		$file         = $themeFolder . $page . '.html';

		if (is_dir($backupFolder)) {
			if (file_exists($file)) {
				$content = file_get_contents($themeFolder . $page . '.html');
				$base    = str_replace('/admin', '', PUBLIC_THEME_PATH) . 'themes/' . $this->getTheme() . '/';
				$content = preg_replace('/<base(.*)href=["\'](.*?)["\'](.*?)>/', '<base$1href="' . $base . '"$3>', $content);

				return @file_put_contents($backupFolder . $backupName, $content);
			}
		}

		return false;
	}

	private function saveElements($elements) {
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
		$post_id     = $this->request->post['post_id'] ?? false;
		$file        = sanitizeFileName($this->request->post['file']);
		$themeFolder = $this->getThemeFolder();

		if ($post_id) {
			$type = 'page';

			if ($post_id) {
				if (is_numeric($post_id)) {
					$post_id = [$post_id];
				}

				$this->posts   = new PostSQL();
				$options       = [
					'post_id' => $post_id, 'type' => $type,
				] + $this->global;

				$result  = $this->posts->delete($options);

				if ($result && isset($result['post'])) {
					$message = ['success' => true, 'message' => ucfirst($type) . __(' deleted!')];
				} else {
					$message = ['success' => false, 'message' => sprintf(__('Error deleting %s!'),  $type)];
				}
			}
		} else {
			if (unlink($themeFolder . DS . $file)) {
				$message = ['success' => true, 'message' => __('File deleted!')];
			} else {
				$message = ['success' => false, 'message' => __('Error deleting file!')];
			}
		}

		$this->response->setType('json');
		$this->response->output($message);
	}

	function rename() {
		$post_id     = $this->request->post['post_id'] ?? false;
		$file        = sanitizeFileName($this->request->post['file']);
		$newfile     = sanitizeFileName($this->request->post['newfile']);
		$duplicate   = $this->request->post['duplicate'] ?? 'false';
		$themeFolder = $this->getThemeFolder();

		if (strpos($newfile, '.html') === false) {
			$newfile .= '.html';
		}

		$currentFile = $themeFolder . DS . $file;
		$targetFile  = dirname($currentFile) . DS . slugify(basename($newfile)); //save in same folder

		$message = ['success' => false, 'message' => __('Error!')];

		if ($post_id) {
			if ($newfile) {
				$type          = 'page';
				$name          = sanitizeFileName($this->request->post['name']);
				$slug          = slugify($name);

				$this->posts   = new PostSQL();
				$data          = $this->posts->get(['post_id' => $post_id]);

				if ($duplicate === 'true') {
					$data = $this->posts->get(['post_id' => $post_id]);

					if ($data) {
						unset($data['post_id']);
						$id = rand(1, 1000);

						foreach ($data['post_content'] as &$content) {
							unset($content['post_id']);

							if ($content['language_id'] == $this->global['language_id']) {
								$content['name'] = $name;
								$content['slug'] = $slug;
							} else {
								$content['name'] .= ' [' . __('duplicate') . ']';
								$content['slug'] .= '-' . __('duplicate') . "-$id";
							}
						}

						if (isset($data['post_to_taxonomy_item'])) {
							foreach ($data['post_to_taxonomy_item'] as &$item) {
								$taxonomy_item[] = $item['taxonomy_item_id'];
							}
						}

						if (isset($data['post_to_site'])) {
							foreach ($data['post_to_site'] as &$item) {
								$site_id[] = $item['site_id'];
							}
						}

						$startTemplateUrl = $data['template'] ?? "content/$type.html";
						$template         = "content/$slug.html";

						if (! @copy($themeFolder . DS . $startTemplateUrl, $themeFolder . DS . $template)) {
							$template = $data['template'] ?? '';
						}

						$result = $this->posts->add([
							'post' => [
								'post_content'  => $data['post_content'],
								'taxonomy_item' => $taxonomy_item ?? [],
								'template'      => $template,
							] + $data,
							'site_id' => $site_id,
						]);

						if ($result && isset($result['post'])) {
							$message = ['success' => true, 'url' => url('content/page/index', ['slug' => $slug, 'post_id' => $post_id]), 'message' => ucfirst($type) . ' ' . __('duplicated') . '!'];
						} else {
							$message = ['success' => false, 'message' => sprintf(__('Error duplicating %s!'),  $type)];
						}
					}
				} else {
					$data = [
						'post_content'  => ['name' => $name, 'slug' => $slug],
						'post_id'       => $post_id,
						'language_id'   => $this->global['language_id'],
					];
					$result  = $this->posts->editContent($data);

					if ($result && isset($result['post_content'])) {
						$message = ['success' => true, 'url' => url('content/page/index', ['slug' => $slug, 'post_id' => $post_id]), 'message' => ucfirst($type) . ' ' . __('renamed') . '!'];
					} else {
						$message = ['success' => false, 'message' => sprintf(__('Error renaming %s!'),  $type)];
					}
				}
			}
		} else {
			if ($duplicate === 'true') {
				if (@copy($currentFile, $targetFile)) {
					$message = ['success' => true, 'newfile' => $newfile,  'message' => __('File copied!'),  'url' => $newfile];
				} else {
					$message = ['success' => false, 'message' => __('Error copying file!')];
				}
			} else {
				if (rename($currentFile, $targetFile)) {
					$message = ['success' => true, 'newfile' => $newfile, 'message' => __('File renamed!')];
				} else {
					$message = ['success' => false, 'message' => __('Error renaming file!')];
				}
			}
		}

		$this->clearTemplateListCache(sanitizeFileName($this->request->get['theme'] ?? false));
		$this->response->setType('json');
		$this->response->output($message);
	}

	function save() {
		$file             = $this->request->post['file'] ?? '';
		$folder           = $this->request->post['folder'] ?? '';
		$startTemplateUrl = $this->request->post['startTemplateUrl'] ?? '';
		$name             = $this->request->post['name'] ?? '';
		$content          = $this->request->post['content'] ?? 'Lorem ipsum';
		$type             = $this->request->post['type'] ?? false;
		$addMenu          = $this->request->post['add-menu'] ?? false;
		$menu_id          = $this->request->post['menu_id'] ?? false;
		$theme            = $this->getTheme();
		$url              = '';

		$file             = sanitizeFileName(str_replace('.html', '', $file)) . '.html';
		$folder           = sanitizeFileName($folder);

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
						] + $this->global,
						'site_id' => [$this->global['site_id']], ] + $this->global);

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
						] + $this->global,
						'site_id' => [$this->global['site_id']], ] + $this->global);

					if ($result['product']) {
						$success    = true;
						$route      = "product/{$type}/index";
						$url        = \Vvveb\url($route, ['slug'=> $slug]);
					}

				break;
			}
		}

		$html        = $this->request->post['html'] ?? false;
		$elements    = $this->request->post['elements'] ?? false;
		$setTemplate = $this->request->post['setTemplate'] ?? false;

		$view         = View::getInstance();
		$view->noJson = true;
		$success      = false;
		$text      		 = '';

		$baseUrl     = '/themes/' . $theme . '/' . ($folder ? $folder . '/' : '');
		$themeFolder = $this->getThemeFolder();

		if ($startTemplateUrl) {
			$startTemplate = $themeFolder . DS . $startTemplateUrl;

			if (file_exists($startTemplate)) {
				if (! ($html = @file_get_contents($startTemplate))) {
					$text .= sprintf(__('%s is not readable!'), $startTemplate);
				}
			} else {
				$text .= sprintf(__('%s does not exist!'), $startTemplate);
			}

			$html = preg_replace('@<base href[^>]+>@', '<base href="' . $baseUrl . '">', $html);
		}

		if (! $url) {
			$url = "$baseUrl/$file";
		}

		$data = compact('file', 'name', 'url', 'startTemplateUrl');

		if ($elements) {
			if ($this->saveElements($elements)) {
				$success = true;
				$text    = __('Elements saved!') . "\n";
			} else {
				$success = false;
				$text    = __('Error saving elements!') . "\n";
			}
		}

		//if plugins template use public path
		$isPlugin = false;

		if (substr_compare($file,'/plugins/', 0, 9) === 0) {
			$fileName = DIR_PUBLIC . DS . ($folder ? $folder . DS : '') . $file;
			$isPlugin = true;
		} else {
			$fileName = $themeFolder . DS . ($folder ? $folder . DS : '') . $file;
		}

		if (! $startTemplateUrl && ! $isPlugin) {
			$backupFolder = $themeFolder . DS . 'backup' . DS;

			if (is_writable($backupFolder)) {
				if ($this->backup($file)) {
				} else {
					$success = false;
					$text .= __('Error saving revision!') . "\n";
				}
			} else {
				$success = false;
				$text .= sprintf(__('%s folder not writable!'), $theme . DS . 'backup') . "\n";
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

		if ($html) {
			if (@file_put_contents($fileName, $html)) {
				$globalOptions = [];
				//keep css inline for email templates
				if (strpos($fileName, '/email/') !== false) {
					$globalOptions['inline-css'] = true;
				}

				$html = $this->saveGlobalElements($html, $globalOptions);

				$success = true;
				$text .= __('File saved!');
			} else {
				if (! is_writable($fileName)) {
					$text .= sprintf(__('%s is not writable!'), $file);
				} else {
					$text .= sprintf(__('Error saving %s!'), $file);
				}
			}
		} else {
			$text .= __('Page html empty!');
		}

		$cssFile = DS . 'css' . DS . 'custom.css';

		if (! is_writable($themeFolder . $cssFile)) {
			$text .= '<br/>' . sprintf(__('%s is not writable!'), $theme . $cssFile);
		}

		if (CacheManager::clearCompiledFiles('app') && CacheManager::delete()) {
		}

		$data += ['success' => $success, 'message' => $text];

		$this->response->setType('json');
		$this->response->output($data);
	}
}
