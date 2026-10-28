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

use function Vvveb\__;
use Vvveb\Controller\Base;
use function Vvveb\getUrl;
use function Vvveb\model;
use function Vvveb\removeJsonComments;
use function Vvveb\sanitizeFileName;
use function Vvveb\slugify;
use Vvveb\Sql\MenuSQL;
use Vvveb\Sql\PostSQL;
use Vvveb\Sql\ProductSQL;
use Vvveb\System\Cache;
use Vvveb\System\CacheManager;
use Vvveb\System\Core\View;
use Vvveb\System\Event;
use Vvveb\System\PageCache;
use Vvveb\System\Sites;
use function Vvveb\url;

class PageBuilder extends Base {
	use GlobalTrait;

	/**
	 * @var string Date format for page revisions.
	 */
	protected string $revisionDateFormat = 'Y-m-d_H:i:s';

	/**
	 * @var array<string, mixed> Theme configuration.
	 */
	private array $themeConfig = [];

	/**
	 * @var array<int, string> Folders to skip during file operations.
	 */
	private array $skipFolders = ['src', 'source', 'backup', 'import'];

	/**
	 * @var array<int, string> Files to skip.
	 */
	private array $skipFiles = [];

	/**
	 * Initializes the controller and loads theme configuration.
	 *
	 * @return string|null
	 */
	public function init(): ?string {
		$return = parent::init();
		$this->loadThemeConfig();

		return $return;
	}

	/**
	 * Proxy for oEmbed requests.
	 *
	 * @return void
	 */
	public function oEmbedProxy(): void {
		$url = $this->request->get['url'] ?? '';

		if (!$url) {
			return;
		}
		$result = getUrl($url);

		$this->response->setType('json');
		$this->response->output($result);
	}

	/**
	 * Gets the current active theme name.
	 *
	 * @return string Active theme name.
	 */
	private function getTheme(): string {
		return sanitizeFileName($this->request->get['theme'] ?? Sites::getTheme($this->global['site_id']));
	}

	/**
	 * Gets the theme directory folder path.
	 *
	 * @return string Theme folder path.
	 */
	private function getThemeFolder(): string {
		return DIR_THEMES . $this->getTheme();
	}

	/**
	 * Loads the theme configuration.
	 *
	 * @return void
	 */
	private function loadThemeConfig(): void {
		$configFile        = $this->getThemeFolder() . DS . 'theme.json';
		$this->themeConfig = [];

		if (file_exists($configFile) &&
			($json = file_get_contents($configFile)) &&
			$config = json_decode(removeJsonComments($json), true)) {
			$this->themeConfig = $config;
		} else {
			//default config
			$this->themeConfig = [
				'cssFramework' => 'bootstrap5', //vanilla or empty, bootstrap, tailwind, bulma etc
				'jsFramework'  => 'vanilla', //vanilla or empty, react, vue etc
			];
		}
	}

	/**
	 * Loads the template list for the specified theme.
	 *
	 * @param string|null $theme Theme name.
	 *
	 * @return array<string, mixed> Template list.
	 */
	private function loadTemplateList(?string $theme = null): array {
		$list = $this->themeConfig['pages'] ?? [];

		$pages = $list + Cache::getInstance()->cache(
			APP,
			'template-list-' . $theme,
			function() use ($theme) {
				$templates =  \Vvveb\getTemplateList($theme);
				//add a prefix for templates like 404 to avoid showing them first
				foreach ($templates as $key => $template) {
					if (is_numeric($key) || ($key !== '' && is_numeric($key[0]))) {
						unset($templates[$key]);
						$templates["t-$key"] = $template;
					}
				}

				return $templates;
			},
			604800
		);

		[$pages] = Event::trigger(__CLASS__, __FUNCTION__, $pages);

		return $pages;
	}

	/**
	 * Clears the template list cache for the specified theme.
	 *
	 * @param ?string $theme Theme name.
	 *
	 * @return bool True on success, false on failure.
	 */
	private function clearTemplateListCache(?string $theme = null): bool {
		return Cache::getInstance()->delete(APP, 'template-list-' . sanitizeFileName($theme));
	}

	/**
	 * Loads editor data, such as menus.
	 *
	 * @return array<string, mixed> Loaded editor data.
	 */
	private function loadEditorData(): array {
		$data = [];

		//menu list
		$menuSQL = new MenuSQL();
		$results = $menuSQL->getAll($this->global);

		$data += $results;

		[$data] = Event::trigger(__CLASS__, __FUNCTION__, $data);

		return $data;
	}

	/**
	 * Loads theme sections, components, inputs, and styles.
	 *
	 * @return void
	 */
	private function loadThemeAssets(): void {
		$themeFolder = $this->getThemeFolder();
		$view        = &$this->view;
		$assets      = [];

		foreach (['inputs', 'components', 'blocks', 'sections', 'styles'] as $type) {
			$glob  = glob("$themeFolder/$type/*.js") ?: [];

			foreach ($glob as &$file) {
				$base                  = str_replace('.js', '', basename($file));
				$assets[$type][$base]  = str_replace($themeFolder, $view->themeBaseUrl, $file);
			}
		}

		$vvvebJs = '/js/vvvebjs.js';

		if (file_exists($themeFolder . $vvvebJs)) {
			$assets['js']['vvvebjs'] = $view->themeBaseUrl . $vvvebJs;
		}

		[$assets] =
		Event::trigger(__CLASS__, __FUNCTION__, $assets);

		foreach ($assets as $type => $files) {
			$name        = 'theme' . ucfirst($type); //themeSections
			$view->$name = $files;
		}
	}

	/**
	 * Proxy handler (currently empty stub).
	 *
	 * @return void
	 */
	public function proxy(): void {}

	/**
	 * Loads list of pages and updates the view pages configuration.
	 *
	 * @param string|false $theme     Theme name or false.
	 * @param string       $urlParams URL parameter string.
	 *
	 * @return void
	 */
	protected function pages(string|false $theme = false, string $urlParams = ''): void {
		$view               = View::getInstance();
		$view->pages ??= $this->loadTemplateList($theme);

		$this->posts   = new PostSQL();
		$options       = [
			'type'  => 'page',
			'limit' => 100,
		] + $this->global;

		$results = $this->posts->getAll($options);
		$posts   = [];

		if ($results && isset($results['post'])) {
			foreach ($results['post'] as $post) {
				//skip posts without translations
				if (!isset($post['slug']) || !isset($post['name'])) {
					continue;
				}

				$slug   = htmlspecialchars($post['slug']);
				$params = ['slug' => $slug, 'post_id' => $post['post_id']];
				if ($this->global['path']) {
					$params['path'] = '/' . $this->global['path'];
				}

				$url = url('content/page/index', $params);

				$templateFile        = $post['template'] ?: 'content/page.html';
				$posts["$slug-page"] = [
					'name'      => "$slug-page",
					'file'      => $templateFile,
					'filename'  => $templateFile,
					'url'       => $url . ($theme ? '?theme=' . $theme : '') . $urlParams,
					'title'     => htmlspecialchars($post['name']),
					'post_id'   => $post['post_id'],
					'folder'    => '',
					'className' => 'page',
				];
			}
		}

		if ($posts) {
			$view->pages = $posts + $view->pages;
		}
	}

	/**
	 * Main index action for the editor.
	 * Sets up template lists, endpoint URLs, and loads requested templates.
	 *
	 * @return void
	 */
	public function index(): void {
		$theme              = $this->getTheme();
		$themeParam         = (isset($this->request->get['theme']) ? '&theme=' . $theme : '');
		$view               = View::getInstance();
		$view->themeBaseUrl = PUBLIC_PATH . 'themes/' . $theme . '/';
		$view->themeName    = $theme;

		$view->themeFonts   = Cache::getInstance()->cache(APP, 'fonts-list-' . $theme, function() use ($theme) {
			$fonts = \Vvveb\System\Media\Font::themeFonts($theme);
			$names = [];

			if ($fonts) {
				foreach ($fonts as $font) {
					if (isset($font['font-family'])) {
						$names[$font['font-family']] = null;
					}
				}
			}

			return $names;
		}, 604800);

		$this->loadThemeAssets();
		$urlParams = '';
		//if admin host is different than site host (for multi site install) use full url including host
		$host = $_SERVER['HTTP_HOST'] . ($this->global['path'] ? '/' . $this->global['path'] : '');
		if ($host !== $this->global['site_url']) {
			//$urlPrefix = '//' . $this->global['site_url'];
			$urlParams = (isset($this->request->get['theme']) ? '&' : '?') . 'site_id=' . $this->global['site_id'];
		}

		$this->pages(isset($this->request->get['theme']) ? $theme : false, $urlParams);

		$homeUrl      = V_SUBDIR_INSTALL . '/' . ($this->global['path'] ?: '');
		$url          = sanitizeFileName($this->request->get['url'] ?? $homeUrl, false);
		$name         = $this->request->get['name'] ?? '';
		$template     = $this->request->get['template'] ?? '';
		$folder       = $this->request->get['folder'] ?? false;
		$route        = \Vvveb\getUrlRoute($url);
		$className    = 'url';
		$current_page = [];

		$site_name = '';
		if ($url === $homeUrl && !$template) {
			$site = Sites::getSiteData($this->global['site_id']);

			if ($site) {
				$template  = ($site['template'][$this->global['language_id']] ?? $site['template'][0] ?? '') ?: 'index.html';
				$site_name = $site['name'] ?? '';
			} else {
				$template = 'index.html';
			}
		}

		$file = $template;

		//check if url and template is relative
		if (str_contains($url, '//') || str_contains($template, '..')) {
			$this->notFound();
		}

		//check if the url has extension and is a html file and exists in the theme folder
		if (str_contains($url, '.')) {
			if (str_ends_with($url, '.html')) {
				$this->validateTemplateExists($url, $theme);
			} else {
				$this->notFound(sprintf(__('%s does not exist!'), $url));
			}
		}

		//check if template belongs to theme and is a html file
		if (str_ends_with($template, '.html')) {
			$this->validateTemplateExists($template, $theme);
		} else {
			$this->notFound(sprintf(__('%s does not exist!'), __('template')));
		}

		if ($route && isset($route['module'])) {
			$className = $this->routeToClassName($route, $current_page);
		}

		$key  = slugify($url);
		$slug = slugify(str_replace('.html', '', $template));

		if (!$name) {
			//if in page list get pretty name
			if (isset($view->pages[$slug])) {
				$name = $view->pages[$slug]['title'];
			} else {
				$name = \Vvveb\humanReadable($url);
				if (!$name) {
					$name = $slug;
				}
			}
		}

		if ($url === $homeUrl) {
			$key = $slug = 'index';
			if ($site_name) {
				$name .= ' - ' . $site_name;
			}
		}

		$global = ($file === 'index.html');

		$current_page += [
			'name'      => $key,
			'file'      => $file,
			'filename'  => $file,
			'url'       => $url . (isset($this->request->get['theme']) ? '?theme=' . $theme : '') . $urlParams,
			'title'     => $name,
			'folder'    => '',
			'className' => $className,
			'global'    => $global,
		];

		$view->pages = [$key => $current_page] + $view->pages;

		$this->setEditorUrls($themeParam);
		//encrypted key for component render requests
		$key           = \Vvveb\getConfig('app.key');
		$keyData       = $this->global['admin_id'] . '-' . $this->global['site_id'];
		$view->userKey = \Vvveb\encrypt($key, $keyData);
		$view->adminId = $this->global['admin_id'];

		$view->cssFramework = $this->themeConfig['cssFramework'] ?? 'bootstrap5';
		$view->jsFramework  = $this->themeConfig['jsFramework'] ?? 'vanilla';
		$view->templates    = \Vvveb\getTemplateList($theme);
		$view->folders      = \Vvveb\getThemeFolderList($theme);
		$view->data         = $this->loadEditorData();
	}

	/**
	 * Set all editor/media/revisions endpoint URLs on the view.
	 *
	 * @param string $themeParam The theme query-string parameter (e.g. '&theme=default').
	 *
	 * @return void
	 */
	private function setEditorUrls(string $themeParam): void {
		$admin_path          = \Vvveb\adminPath();
		$mediaControllerPath = $admin_path . 'index.php?module=media/media';
		$controllerPath      = $admin_path . 'index.php?module=editor/page-builder' . $themeParam;
		$revisionsPath       = $admin_path . 'index.php?module=editor/revisions' . $themeParam;
		$reusablePath        = $admin_path . 'index.php?module=editor/reusable' . $themeParam;

		//media endpoints
		$this->view->scanUrl           = "$mediaControllerPath&action=scan";
		$this->view->uploadUrl         = "$mediaControllerPath&action=upload";
		$this->view->deleteUrl         = "$mediaControllerPath&action=delete";
		$this->view->renameUrl         = "$mediaControllerPath&action=rename";

		//editor endpoints
		$this->view->namespaceUrl      =  $admin_path . 'index.php?module=editor';
		$this->view->editorUrl         =  $controllerPath;
		$this->view->saveUrl           = "$controllerPath&action=save";
		$this->view->deleteFileUrl     = "$controllerPath&action=delete";
		$this->view->renameFileUrl     = "$controllerPath&action=rename";
		$this->view->saveReusableUrl   = "$reusablePath&action=save";
		$this->view->oEmbedProxyUrl    = "$controllerPath&action=oEmbedProxy";

		$this->view->revisionsUrl      = "$revisionsPath&action=revisions";
		$this->view->revisionLoadUrl   = "$revisionsPath&action=load";
		$this->view->revisionDeleteUrl = "$revisionsPath&action=delete";
		$this->view->linkUrl           = $admin_path . 'index.php?module=content/post&action=urlAutocomplete&type=key-value';
	}

	/**
	 * Compute the relative base path (../) for a file based on its folder depth.
	 *
	 * @param string $fileBase The file path including folder prefix.
	 *
	 * @return string The relative base (e.g. '../../' for 2 levels deep).
	 */
	private function computeRelativeBase(string $fileBase): string {
		$relativeBase = '';
		$pos          = 0;

		if ($fileBase) {
			while (($pos = strpos($fileBase, '/', $pos + 1)) !== false) {
				$relativeBase .= '../';
			}
		}

		return $relativeBase;
	}

	/**
	 * Check that a template file (plugin or theme) exists, or call notFound + exit.
	 *
	 * @param string $path  The template path to check (URL or template name).
	 * @param string $theme The active theme name.
	 *
	 * @return void
	 */
	private function validateTemplateExists(string $path, string $theme): void {
		if (str_starts_with($path, '/plugins/')) {
			$pluginFile = substr($path, 9);
			$pluginFile = preg_replace('@^(.+/)@', '$1/public/', $pluginFile);

			if (!file_exists(DIR_PLUGINS . $pluginFile)) {
				$this->notFound(sprintf(__('%s does not exist!'), $pluginFile));
			}
		} else {
			//strip theme prefix from URL paths; bare template names pass through unchanged
			$relative = preg_replace('@.+themes/[^\/]+/@', '', $path);

			if (!file_exists(DIR_THEMES . $theme . DS . $relative)) {
				$this->notFound(sprintf(__('%s does not exist!'), $relative));
			}
		}
	}

	/**
	 * Map a resolved route to a CSS className and populate current_page identifiers.
	 *
	 * @param array<string, mixed> $route        The resolved route array.
	 * @param array<string, mixed> $current_page Current-page array (modified in-place).
	 *
	 * @return string The className ('product', 'page', or 'url').
	 */
	private function routeToClassName(array $route, array &$current_page): string {
		$idKey     = 'post_id';
		$className = 'page';

		if ($route['module'] === 'product/product/index') {
			$className = 'product';
			$idKey     = 'product_id';
		} elseif ($route['module'] !== 'content/post/index' && $route['module'] !== 'content/page/index') {
			return 'url';
		}

		if (isset($route[$idKey])) {
			$current_page[$idKey] = $route[$idKey];
		} elseif (isset($this->request->get[$idKey])) {
			$current_page[$idKey] = $this->request->get[$idKey];
		} elseif (isset($route['slug'])) {
			$current_page['slug'] = htmlspecialchars($route['slug']);
		}

		return $className;
	}

	/**
	 * Creates a backup copy of a page.
	 *
	 * @param string $page Page name/file.
	 *
	 * @return int|false Bytes written or false on failure.
	 */
	private function backup(string $page): int|false {
		$themeFolder  = $this->getThemeFolder() . DS;
		$backupFolder = $themeFolder . 'backup' . DS;
		$page         = str_replace('.html', '', sanitizeFileName($page));
		$backupName   = str_replace(DS, '-', $page) . '@' . str_replace(':', ';', date($this->revisionDateFormat)) . '.html';
		$file         = $themeFolder . $page . '.html';

		if (is_dir($backupFolder)) {
			if (file_exists($file)) {
				$content = file_get_contents($file);

				if (str_contains($content, '<base')) {
					$content = preg_replace('/<base(.*)href=["\'](.*?)["\'](.*?)>/', '<base$1href="../"$3>', $content);
				} else {
					$content = str_replace('<head>', "<head>\n<base href=\"../\">\n", $content);
				}

				return @file_put_contents($backupFolder . $backupName, $content);
			}
		}

		return false;
	}

	/**
	 * Saves modified components/elements from the editor page.
	 *
	 * @param array<int, array<string, mixed>> $elements Components list with their fields.
	 *
	 * @return bool True on success.
	 */
	private function saveElements(array $elements): bool {
		$components = [];

		foreach ($elements as $element) {
			$component = $element['component'];
			$type      = $element['type'];
			$id        = $element['id'];
			$fields    = $element['fields'];

			if (!preg_match('/^[a-zA-Z][a-zA-Z0-9_\-]*$/', $component)) {
				continue;
			}

			$componentName = "\Vvveb\Component\\$component";

			if (!class_exists($componentName, false)) {
				include_once DIR_ROOT . "app/component/$component.php";
			}

			if (!isset($components[$component])) {
				$components[$component] = new $componentName();
			}

			$components[$component]->editorSave($id, $fields, $type);
		}

		return true;
	}

	/**
	 * Deletes a template file or database post/page.
	 *
	 * @return void
	 */
	public function delete(): void {
		$post_id     = $this->request->post['post_id'] ?? false;
		$file        = sanitizeFileName($this->request->post['file'] ?? '');
		$themeFolder = $this->getThemeFolder();

		if ($post_id) {
			$type = 'page';

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
				$message = ['success' => false, 'message' => sprintf(__('Error deleting %s!'), $type)];
			}
		} else {
			if ($file && file_exists($themeFolder . DS . $file)) {
				if (unlink($themeFolder . DS . $file)) {
					$message = ['success' => true, 'message' => __('File deleted!')];
				} else {
					$message = ['success' => false, 'message' => __('Error deleting file!')];
				}
			} else {
				$message = ['success' => false, 'message' => __('File does not exist!')];
			}
		}

		$this->response->setType('json');
		$this->response->output($message);
	}

	/**
	 * Renames or duplicates a template file or database page/product.
	 *
	 * @return void
	 */
	public function rename(): void {
		$post_id     = $this->request->post['post_id'] ?? false;
		$product_id  = $this->request->post['product_id'] ?? false;
		$file        = sanitizeFileName($this->request->post['file'] ?? '');
		$newfile     = sanitizeFileName($this->request->post['newfile'] ?? '');
		$duplicate   = ($this->request->post['duplicate'] ?? 'false') === 'true';
		$themeFolder = $this->getThemeFolder();
		$name        = $this->request->post['name'] ?? $newfile;
		$theme       = $this->getTheme();
		$dir         = dirname($file);

		if ($newfile) {
			$slug    = sanitizeFileName(str_ireplace('.html', '', (basename($newfile))));
			$newfile = $slug . '.html';
		}

		$currentFile = $themeFolder . DS . $file;
		$targetFile  = dirname($currentFile) . DS . $newfile; //save in same folder

		$message = ['success' => false, 'message' => __('Error!')];

		if ($post_id || $product_id) {
			$model      = 'post';
			$type       = 'page';
			$namespace  = 'content';
			$model_id   = $post_id;

			if ($product_id) {
				$model_id   = $product_id;
				$namespace  = 'product';
				$model      = 'product';
				$type       = 'product';
			}

			if ($newfile) {
				$name          = sanitizeFileName($this->request->post['name'] ?? $name);
				$slug          = slugify($name);

				$this->posts   = model($model);

				if ($duplicate) {
					$data = $this->posts->get([$model . '_id' => $model_id]);

					if ($data) {
						unset($data[$model . '_id']);
						$id = random_int(1, 1000);

						foreach ($data[$model . '_content'] as &$content) {
							unset($content[$model . '_id']);

							if ($content['language_id'] == $this->global['language_id']) {
								$content['name'] = $name;
								$content['slug'] = $slug;
							} else {
								$content['name'] .= ' [' . __('duplicate') . ']';
								$content['slug'] .= '-' . __('duplicate') . "-$id";
							}
						}

						$taxonomy_item = [];
						if (isset($data[$model . '_to_taxonomy_item'])) {
							foreach ($data[$model . '_to_taxonomy_item'] as &$item) {
								$taxonomy_item[] = $item['taxonomy_item_id'];
							}
						}

						$site_id = [];
						if (isset($data[$model . '_to_site'])) {
							foreach ($data[$model . '_to_site'] as &$item) {
								$site_id[] = $item['site_id'];
							}
						}

						$startTemplateUrl = $data['template'] ?? "$namespace/$type.html";
						$template         = "$namespace/$slug.html";

						if (!@copy($themeFolder . DS . $startTemplateUrl, $themeFolder . DS . $template)) {
							$template = $data['template'] ?? '';
						}

						$new = [
							$model => [
								'taxonomy_item' => $taxonomy_item,
								'template'      => $template,
							] + $data,
							$model . '_content' => $data[$model . '_content'],
							'site_id'           => $site_id,
						] + $data + $this->global;

						$result = $this->posts->add($new);

						$newfile = PUBLIC_PATH . "themes/$theme/" . ($dir ? $dir . '/' : '') . $template;

						if ($result && isset($result[$model])) {
							$model_id = $result[$model];
							$message  = ['success' => true, 'name' => $name, 'slug' => $slug, $model . '_id' => $model_id, 'newfile' => $newfile, 'url' => url("$namespace/$type/index", ['slug' => $slug, $model . '_id' => $model_id]), 'message' => ucfirst($type) . ' ' . __('duplicated') . '!'];
						} else {
							$message = ['success' => false, 'message' => sprintf(__('Error duplicating %s!'), $type)];
						}
					}
				} else {
					$data = [
						$model . '_content' => ['name' => $name, 'slug' => $slug],
						$model . '_id'      => $model_id,
						'language_id'       => $this->global['language_id'],
					];
					$result  = $this->posts->editContent($data);

					$newfile = PUBLIC_PATH . "themes/$theme/" . ($dir ? $dir . '/' : '') . $newfile;

					if ($result && isset($result[$model . '_content'])) {
						$message = ['success' => true, 'name' => $name, 'slug' => $slug, $model . '_id' => $model_id, 'newfile' => $newfile, 'url' => url("$namespace/$type/index", ['slug' => $slug, $model . '_id' => $model_id]), 'message' => ucfirst($type) . ' ' . __('renamed') . '!'];
					} else {
						$message = ['success' => false, 'message' => sprintf(__('Error renaming %s!'), $type)];
					}
				}
			}
		} else {
			if (!$file || !$newfile) {
				$message = ['success' => false, 'message' => __('File and new file name are required!')];
			} else {
				$newfile = PUBLIC_PATH . "themes/$theme/" . ($dir ? $dir . '/' : '') . $newfile;

				if ($duplicate) {
					if (@copy($currentFile, $targetFile)) {
						$message = ['success' => true, 'newfile' => $newfile, 'name' => $name, 'message' => __('File copied!'),  'url' => $newfile];
					} else {
						$message = ['success' => false, 'message' => __('Error copying file!')];
					}
				} else {
					if (rename($currentFile, $targetFile)) {
						$message = ['success' => true, 'newfile' => $newfile, 'name' => $name, 'message' => __('File renamed!')];
					} else {
						$message = ['success' => false, 'message' => __('Error renaming file!')];
					}
				}
			}
		}

		$this->clearTemplateListCache($this->request->get['theme'] ?? false);
		$this->response->setType('json');
		$this->response->output($message);
	}

	/**
	 * Create a new page/post or product record from the editor save action.
	 *
	 * @param string $type             Content type ('page', 'post', or 'product').
	 * @param string $name             Display name.
	 * @param string $slug             URL slug.
	 * @param string $content          HTML content.
	 * @param string $image            Image path.
	 * @param string $templateType     'global' or 'custom'.
	 * @param string $startTemplateUrl Starter template URL.
	 *
	 * @return array{0:string,1:string,2:bool} [file, url, success]
	 */
	private function createContent(string $type, string $name, string $slug, string $content, string $image, string $templateType, string $startTemplateUrl): array {
		$file    = '';
		$url     = '';
		$success = false;

		switch ($type) {
			case 'page':
			case 'post':
				$default  = sanitizeFileName(($templateType === 'global') ? "content/$type.html" : "content/$slug.html");
				$template = ($templateType === 'global' && $startTemplateUrl) ? $startTemplateUrl : $default;
				$file     = $template;

				$post    = new PostSQL();
				$result  = $post->add([
					'post' => [
						'template' => $template,
						'type'     => $type,
						'image'    => $image,
					] + $this->global,
					'post_content' => [[
						'slug'        => $slug,
						'name'        => $name,
						'content'     => $content,
						'language_id' => $this->global['language_id'],
					]],
					'site_id' => [$this->global['site_id']],
				] + $this->global);

				if ($result['post'] ?? false) {
					$post_id = $result['post'];
					$success = true;
					$url     = \Vvveb\url("content/{$type}/index", ['slug' => $slug, 'post_id' => $post_id]);
				}

				break;

			case 'product':
				$default  = sanitizeFileName(($templateType === 'global') ? "product/$type.html" : "product/$slug.html");
				$template = ($templateType === 'global' && $startTemplateUrl) ? $startTemplateUrl : $default;
				$price    = $this->request->post['price'] ?? 0;
				$file     = $template;

				$product = new ProductSQL();
				$result  = $product->add([
					'product' => [
						'model'    => '',
						'image'    => $image,
						'status'   => 1, //active
						'template' => $template,
						'price'    => $price,
					] + $this->global,
					'product_content' => [[
						'slug'        => $slug,
						'name'        => $name,
						'content'     => $content,
						'language_id' => $this->global['language_id'],
					]],
					'site_id' => [$this->global['site_id']],
				] + $this->global);

				if ($result['product'] ?? false) {
					$product_id = $result['product'];
					$success    = true;
					$url        = \Vvveb\url("product/{$type}/index", ['slug' => $slug, 'product_id' => $product_id]);
				}

				break;
		}

		return [$file, $url, $success];
	}

	/**
	 * Saves templates, pages, or database posts/products, processes backups,
	 * updates menus, and purges compiled caches.
	 *
	 * @return void
	 */
	public function save(): void {
		$file             = $this->request->post['filename'] ?? '';
		$folder           = $this->request->post['folder'] ?? '';
		$startTemplateUrl = $this->request->post['startTemplateUrl'] ?? '';
		$name             = $this->request->post['name'] ?? '';
		$content          = $this->request->post['content'] ?? '<div>Lorem ipsum</div>';
		$image            = $this->request->post['image'] ?? '';
		$type             = $this->request->post['type'] ?? false;
		$templateType     = $this->request->post['template-type'] ?? 'global';
		$addMenu          = $this->request->post['add-menu'] ?? false;
		$menu_id          = $this->request->post['menu_id'] ?? false;
		$uri              = $this->request->post['url'] ?? '/';
		$theme            = $this->getTheme();
		$folder           = trim(sanitizeFileName($folder), '/');
		$startTemplateUrl = sanitizeFileName($startTemplateUrl);
		$success          = false;
		$url              = '';

		if ($file) {
			$file = sanitizeFileName(str_replace('.html', '', $file)) . '.html';
		}

		$slug    = $name ? slugify($name) : '';

		if ($type && $name) {
			[$file, $url, $success] = $this->createContent($type, $name, $slug, $content, $image, $templateType, $startTemplateUrl);
		}

		$html        = $this->request->post['html'] ?? false;
		$elements    = $this->request->post['elements'] ?? false;

		$view         = View::getInstance();
		$view->noJson = true;
		$messages     = [];

		$baseUrl      = PUBLIC_PATH . 'themes/' . $theme . '/' . ($folder ? $folder . '/' : '');
		$themeFolder  = $this->getThemeFolder();
		$fileBase     = ($folder ? $folder . '/' : '') . $file;
		$relativeBase = $this->computeRelativeBase($fileBase);

		if ($startTemplateUrl) {
			$startTemplate = $themeFolder . DS . $startTemplateUrl;
			if (file_exists($startTemplate)) {
				if (!($html = @file_get_contents($startTemplate))) {
					$messages[] = ['success' => false, 'message' => sprintf(__('%s is not readable!'), $startTemplate)];
				}
			} else {
				$messages[] = ['success' => false, 'message' => sprintf(__('%s does not exist!'), $startTemplate)];
			}

			if ($html) {
				$html = preg_replace('@<base\s+href[^>]+>@', '<base href="' . $relativeBase . '">', $html);
			}
		}

		if (!$url) {
			$url = "$baseUrl$file";
		}

		if ($elements) {
			if ($this->saveElements($elements)) {
				$success    = true;
				$messages[] = ['success' => true, 'message' => __('Elements saved!')];
			} else {
				$success    = false;
				$messages[] = ['success' => false, 'message' => __('Error saving elements!')];
			}
		}

		//if plugins template use public path
		$isPlugin = false;

		if (str_starts_with($file, '/plugins/')) {
			$pluginFile = substr($file, 9);
			$pluginFile = preg_replace('@^(.+/)@', '$1/public/', $pluginFile);
			$fileName   = DIR_PLUGINS . $pluginFile;
			$isPlugin   = true;
		} else {
			$fileName = $themeFolder . DS . ($folder ? $folder . DS : '') . $file;
		}

		if (!$startTemplateUrl && !$isPlugin && $file) {
			$backupFolder = $themeFolder . DS . 'backup' . DS;

			if (is_writable($backupFolder)) {
				if (!$this->backup(($folder ? $folder . DS : '') . $file)) {
					$success    = false;
					$messages[] = ['success' => false, 'message' => __('Error saving revision!')];
				}
			} else {
				$success    = false;
				$messages[] = ['success' => false, 'message' => sprintf(__('%s folder not writable!'), $theme . DS . 'backup')];
			}
		}

		if ($addMenu && $menu_id) {
			$menuData = ['menu_item' => [
				'menu_id'           => $menu_id,
				'url'               => $url,
				'menu_item_content' => [[
					'name'        => $name,
					'slug'        => $slug,
					'language_id' => $this->global['language_id'],
					'content'     => '',
				]],
			]] + $this->global;

			$menus   = new MenuSQL();
			$results = $menus->addMenuItem($menuData);

			if ($results) {
				$messages[] = ['success' => true, 'message' => __('Menu item added!')];
			}
		}

		$data = compact('file', 'name', 'url', 'startTemplateUrl');

		if ($file) {
			if ($html) {
				//reset base href so that html file can be loaded properly in browser directly from folder
				//$html = preg_replace('@<base href[^>]+>@', '', $html);
				$html = preg_replace('@<base href[^>]+>@', '<base href="' . $relativeBase . '">', $html);
				//remove empty value for data attributes
				$html = preg_replace('/(data-[\w\-]+)="\s*"/', '$1', $html);

				if (@file_put_contents($fileName, $html)) {
					$globalOptions = [];
					//keep css inline for email templates
					if (str_contains($fileName, '/email/')) {
						$globalOptions['inline-css'] = true;
					}

					$html = $this->saveGlobalElements($html, $globalOptions);

					$success    = true;
					$messages[] = ['success' => true, 'message' => __('File saved!')];
				} else {
					$success = false;
					if (!is_writable($fileName)) {
						$messages[] = ['success' => false, 'message' => sprintf(__('%s is not writable!'), $file)];
					} else {
						$messages[] = ['success' => false, 'message' => sprintf(__('Error saving %s!'), $file)];
					}
				}
			} else {
				$messages[] = ['success' => false, 'message' => __('Page html empty!')];
			}
		}

		$cssFile = DS . 'css' . DS . 'custom.css';

		//try to create custom.css if it doesn't exist
		if (!file_exists($themeFolder . $cssFile) && !@touch($themeFolder . $cssFile)) {
			$messages[] = ['success' => false, 'message' => sprintf(__('Can not create file %s!'), $theme . $cssFile)];
		} else {
			if (!is_writable($themeFolder . $cssFile)) {
				$messages[] = ['success' => false, 'message' => sprintf(__('%s is not writable!'), $theme . $cssFile)];
			}
		}

		if (CacheManager::clearCompiledFiles('app', $this->global['site_id'], $theme)) {
			$this->clearTemplateListCache($this->request->get['theme'] ?? false);

			$pageCache = new PageCache();
			$uri       = preg_replace('/\?.+/', '', $uri);
			$path      = $pageCache->uri($uri);
			$pageCache->purge($path);
		}

		$data += ['success' => $success, 'message' => $messages];

		$this->response->setType('json');
		$this->response->output($data);
	}
}
