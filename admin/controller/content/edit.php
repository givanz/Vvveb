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

namespace Vvveb\Controller\Content;

use function Vvveb\__;
use Vvveb\Controller\Base;
use function Vvveb\humanReadable;
use function Vvveb\model;
use function Vvveb\sanitizeHTML;
use function Vvveb\slugify;
use Vvveb\Sql\categorySQL;
use Vvveb\Sql\SiteSQL;
use Vvveb\System\Cache;
use Vvveb\System\CacheManager;
use Vvveb\System\Core\View;
use Vvveb\System\Event;
use Vvveb\System\Images;
use Vvveb\System\Sites;
use Vvveb\System\User\Admin;
use Vvveb\System\Validator;

class Edit extends Base {
	protected $type = 'post';

	protected $object = 'post';

	protected $revisions = true;

	use TaxonomiesTrait, AutocompleteTrait;

	function sites($selectedSites = []) {
		$sites = new SiteSQL();

		$options = [];

		if (Admin::hasCapability('edit_other_sites')) {
			//unset($options['site_id']);
		} else {
			$options['site_id'] = Admin :: siteAccess();
		}

		$results = $sites->getAll(
			$options + [
				'start'        => 0,
				'limit'        => 100,
			]
		)['site'] ?? [];

		if ($results && $selectedSites) {
			foreach ($results as &$site) {
				$site['selected'] = in_array($site['site_id'], $selectedSites);
			}
		}

		return $results;
	}

	function getThemeFolder() {
		return DIR_THEMES . Sites::getTheme() ?? 'default';
	}

	function index() {
		$view = $this->view;

		$admin_path  = \Vvveb\adminPath();
		$postOptions = [];
		$post        = [];
		$post_id     = $this->request->get[$this->object . '_id'] ?? $this->request->post[$this->object . '_id'] ?? false;

		$controllerPath  = $admin_path . 'index.php?module=media/media';
		$view->scanUrl   = "$controllerPath&action=scan";
		$view->uploadUrl = "$controllerPath&action=upload";
		$view->linkUrl   = $admin_path . 'index.php?module=content/post&action=urlAutocomplete';
		$theme           = Sites::getTheme() ?? 'default';
		$view->themeCss  = '';

		if (file_exists(DIR_THEMES . "$theme/css/admin-post-editor.css")) {
			$view->themeCss .= PUBLIC_PATH . "themes/$theme/css/admin-post-editor.css";
		} else {
			if (file_exists(DIR_THEMES . "$theme/css/style.css")) {
				$view->themeCss .= PUBLIC_PATH . "themes/$theme/css/style.css";
			}
		}

		foreach (['custom.css', 'fonts.css'] as $css) {
			if (file_exists(DIR_THEMES . "$theme/css/$css")) {
				$view->themeCss .= ',' . PUBLIC_PATH . "themes/$theme/css/$css";
			}
		}
		//$view->themeCss        = PUBLIC_PATH . "themes/$theme/css/style.css";

		$viewCapability = 'view_other_posts';

		if ($this->object == 'product') {
			$viewCapability = 'view_other_products';
		}

		if ($post_id) {
			$postOptions[$this->object . '_id'] = (int)$post_id;
		} else {
			if (isset($this->request->get['slug'])) {
				$postOptions['slug'] = $this->request->get['slug'];
			}
		}

		if (isset($this->request->get['type'])) {
			$this->type = $this->request->get['type'];
		}

		if ($postOptions) {
			$posts = model($this->object);

			$postOptions['type'] = $this->type;
			$options             = $postOptions + $this->global;

			if (Admin::hasCapability($viewCapability)) {
				unset($options['admin_id']);
			} else {
				$options['admin_id'] = $this->global['admin_id'];
			}

			//get all languages
			//unset($options['language_id']);
			$post                = $posts->get($options);

			if (! $post) {
				$message = sprintf(__('%s not found!'), humanReadable(__($this->type)));
				$this->notFound(['message' => $message, 'title' => $message]);
			}

			//featured image
			if (isset($post['image'])) {
				$post['image_url'] = Images::image($post['image'], $this->object);
			}

			//gallery
			if (isset($post[$this->object . '_image'])) {
				$post['images'] = Images::images($post[$this->object . '_image'], $this->object);
			}

			//$productImages = $posts->getImages($postOptions);
		} else {
			$post['image_url']  = Images::image('',$this->object);
			$post['updated_at'] = date('Y-m-d H:i:s', time());
		}

		/*
		if (isset($post['updated_at'])) {
			$post['updated_at'] = str_replace(' ', 'T', $post['updated_at']);
		} else {
			$post['updated_at'] = date("Y-m-d\TH:i:s", isset($post['updated_at']) && $post['updated_at'] ? strtotime($post['updated_at']) : time());
		}*/

		$this->type = $post['type'] ?? $this->type;

		if ($this->object == 'product') {
			$route      = "product/{$this->type}/index";
			$altRoute   = "product/{$this->object}/index";
			$controller = 'product';
		} else {
			$route      = "content/{$this->type}/index";
			$altRoute   = "content/{$this->object}/index";
			$controller = 'content';
		}

		if ($this->revisions) {
			$revisions = model($this->object . '_content_revision');
		}

		//get site host for current selected site to use for absolute url
		$url = ['host' => $this->global['host']];

		$revisionsUrl = \Vvveb\url(['module' => "$controller/revisions", 'object' => $this->object, 'type' => $this->type, $this->object . '_id' => $post_id]);
		$name         = '';

		if (isset($post[$this->object . '_content'])) {
			foreach ($post[$this->object . '_content'] as &$content) {
				if (! isset($post['url'])) {
					$post['url']          = \Vvveb\url($route, ['slug'=> $content['slug'], $this->object . '_id' => $post_id] + $url);
					$post['relative-url'] = \Vvveb\url($route, ['slug'=> $content['slug'], $this->object . '_id' => $post_id]);

					if (! $post['url'] || $post['url'] == '//' . $this->global['site_url']) {
						$post['url']          = \Vvveb\url($altRoute, ['slug'=> $content['slug'], $this->object . '_id' => $post_id] + $url);
						$post['relative-url'] = \Vvveb\url($altRoute, ['slug'=> $content['slug'], $this->object . '_id' => $post_id]);
					}
				}
				$language = [];

				if ($content['language_id'] != $this->global['default_language_id']) {
					$code = 'en_US';

					foreach ($this->view->languagesList as $code => $lang) {
						if ($lang['language_id'] == $content['language_id']) {
							break;
						}
					}
					$language = ['language' => $code];
				} else {
					$name = $content['name'];
				}

				$content['url']             = \Vvveb\url($route, $content + $language + $url);
				$content['revision_count']  = 0;

				if (! $content['url'] || $content['url'] == '//' . $this->global['site_url']) {
					$content['url']         = \Vvveb\url($altRoute, $content + $language + $url);
				}

				if ($revisions) {
					$revision = $revisions->getAll([$this->object . '_id' => $post_id, 'language_id' => $content['language_id']]);

					if ($revision) {
						foreach ($revision[$this->object . '_content_revision'] as &$rev) {
							$rev['preview-url'] = $content['url'] . '?revision=preview&created_at=' . $rev['created_at'] . '&language_id=' . $content['language_id'];
							$rev['compare-url'] = $revisionsUrl . '&created_at=' . $rev['created_at'] . '&language_id=' . $content['language_id'];
						}

						$content['revision_count'] = $revision['count'];
						$content['revision']       = $revision[$this->object . '_content_revision'];
					}
				}
			}
		}

		$type_name  = humanReadable(__($this->type));

		$defaultTemplate = \Vvveb\getCurrentTemplate();
		$template        = isset($post['template']) && $post['template'] ? $post['template'] : $defaultTemplate;
		$themeFolder     = $this->getThemeFolder();

		$design_url = '';

		if (isset($post['url'])) {
			$design_url         = \Vvveb\url(['module' => 'editor/editor', 'name' => urlencode($name),  'url' => $post['relative-url'], 'template' => $template, 'host' => $this->global['host']], false);
			$post['design_url'] = $design_url;
		}

		if (! file_exists($themeFolder . DS . $template)) {
			if ($template == $defaultTemplate) {
				$view->template_missing = sprintf(__('Template missing, choose existing template or %screate global template%s for %s.'), '<a href="' . $design_url . '" target="_blank">', '</a>', $type_name);
			} else {
				$view->template_missing = sprintf(__('Template missing, %screate template%s for this  %s.'), '<a href="' . $design_url . '" target="_blank">', '</a>', $type_name);
			}
		}

		if ($this->type != 'page') {
			$view->taxonomies = $this->taxonomies($post[$this->object . '_id'] ?? false);
		}

		$sites = $post[$this->object . '_to_site'] ?? [];

		if ($sites) {
			$sites = array_keys($sites);
		} else {
			if (! $post_id) {
				$sites[] = $this->global['site_id'];
			}
		}

		$view->sitesList = $this->sites($sites);

		list($post, $post_id, $this->type) = Event :: trigger(__CLASS__,__FUNCTION__, $post, $post_id, $this->type);

		$object          = $this->object;
		$view->$object   = $post;
		$view->status    = ['publish' => 'Publish', 'draft' => 'Draft', 'pending' => 'Pending', 'private' => 'Private', 'password' => 'Password', 'future' => 'Future'];

		$view->templates = Cache::getInstance()->cache(APP,'template-list.' . $theme, function () use ($theme) {
			return \Vvveb\getTemplateList($theme, ['email']);
		}, 604800);

		$view->themeFonts = Cache::getInstance()->cache(APP,'fonts-list.' . $theme, function () use ($theme) {
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

		//$validator                 = new Validator([$this->object]);
		//$view->validatorJson       = $validator->getJSON();
		$view->type           = __($this->type);
		$view->type_name      = $type_name;
		$view->posts_list_url =  \Vvveb\url(['module' => $this->list, 'type' => $this->type]);
		$view->revisions_url  =  $revisionsUrl;
		$view->oEmbedProxyUrl = $admin_path . 'index.php?module=editor/editor&action=oEmbedProxy';
	}

	private function addCategory($taxonomy_id, $name) {
		$categories = new CategorySQL();
		$cat        = $categories->addCategory([
			'taxonomy_item' => $this->global + [
				'taxonomy_id' => $taxonomy_id,
			],
			'taxonomy_item_content' => $this->global + ['slug'=> slugify($name), 'name' => $name, 'content' => ''],
		] + $this->global);

		return $category_id = $cat['taxonomy_item'];
	}

	function add() {
		$this->save();
	}

	function save() {
		$view                          = view :: getInstance();
		$post_id                       = $this->request->get[$this->object . '_id'] ?? $this->request->post[$this->object . '_id'] ?? false;
		$this->{$this->object . '_id'} = $post_id;
		$publicPath                    = \Vvveb\publicUrlPath();

		if (isset($this->request->get['type'])) {
			$this->type          = $this->request->get['type'];
		}

		$validator                     = new Validator([$this->object]);
		$validatorContent              = new Validator([$this->object . '_content']);

		if (
			(($errors = $validator->validate($this->request->post)) === true) &&
			(($errors = $validatorContent->validate(current($this->request->post[$this->object . '_content']))) === true)
		) {
			$posts = model($this->object);

			$post = [];

			$post = $this->request->post;

			foreach ($post[$this->object . '_content'] as &$content) {
				$content['name']    = strip_tags($content['name']);
				$content['content'] = sanitizeHTML($content['content']);

				if (isset($content['excerpt'])) {
					$content['excerpt'] = sanitizeHTML($content['excerpt']);
				}
			}

			/*
			if (isset($post['updated_at'])) {
				$post['updated_at'] = str_replace(' ', 'T', $post['updated_at']);
			} else {
				$post['updated_at'] = date("Y-m-d\TH:i:s", time());
			}*/

			//process tags
			if (isset($post['tag'])) {
				foreach ($post['tag'] as $listId => $tags) {
					foreach ($tags as $tagId => $tag) {
						//existing tag add to post taxonomy_item list
						if (is_numeric($tagId)) {
							//$post['taxonomy_item_id'][] = $tagId;
						} else {
							//add new taxonomy_item
							$tagId = $this->addCategory($listId, $tag);
						}
						$post['taxonomy_item_id'][] = $tagId;
					}
				}
			}

			$site_id = $this->request->post['site'] ?? []; //[$this->global['site_id']];

			$post = $post + $this->global;
			$new  = false;

			if ($post_id) {
				/*
				$viewCapability = 'edit_other_posts';

				if ($this->object == 'product') {
					$viewCapability = 'edit_other_products';
				}

				if (Admin::hasCapability($viewCapability)) {
					unset($postOptions['admin_id']);
				} else {
					$postOptions['admin_id'] = $this->global['admin_id'];
				}
				*/

				$post[$this->object . '_id'] = (int)$post_id;
				$data                        = [
					$this->object              => $post,
					$this->object . '_id'      => $post_id,
					$this->object . '_content' => $post[$this->object . '_content'],
					'taxonomy_item_id'         => $post['taxonomy_item_id'] ?? [],
					'site_id'                  => $site_id,
				] + $this->global;

				$result = $posts->edit($data);

				if ($result >= 0) {
					$this->view->success['get'] = ucfirst($this->type) . ' ' . __('saved') . '!';

					if ($this->revisions) {
						$revisions = model($this->object . '_content_revision');

						//foreach ($post[$this->object . '_content'] as &$content) {
						foreach ($this->request->post[$this->object . '_content'] as &$content) {
							if (isset($content['has_changes']) && $content['has_changes'] == 1) {
								$revisions->add(['revision' => [
									$this->object . '_id' => $post_id,
									'language_id'         => $content['language_id'],
									'content'             => $content['content'],
									'created_at'          => date("Y-m-d\TH:i:s", time()),
								] + $this->global]);
							}
						}
					}

					//CacheManager::delete($this->object);
					CacheManager::delete();
				} else {
					$this->view->errors = [$posts->error];
				}
			} else {
				$post['type']                      = $this->type;
				//$post['created_at']                = $post['updated_at'];
				if (isset($post['updated_at']) && ! $post['updated_at']) {
					unset($post['updated_at'], $post[$this->object . '_id']);
				}

				$add = [
					$this->object              => $post,
					$this->object . '_content' => $post[$this->object . '_content'],
					'taxonomy_item_id'         => $post['taxonomy_item_id'] ?? [],
					'site_id'                  => $site_id,
				] + $this->global;

				$return = $posts->add($add);
				$id     = $return[$this->object] ?? false;

				if (! $id) {
					$view->errors = [$posts->error];
				} else {
					CacheManager::delete($this->object);
					$this->request->get[$this->object . '_id'] = $id;

					$post_id = $id;
					$new     = true;

					$message              = ucfirst($this->type) . ' ' . __('saved') . '!';
					$view->success['get'] = $message;
				}
			}

			if (isset($post[$this->object . '_image'])) {
				$productImage = $this->object . 'Image';

				foreach ($post[$this->object . '_image'] as &$image) {
					$image = str_replace($publicPath . 'media/','', $image);
				}

				$posts->$productImage([$this->object . '_id' => $post_id ? $post_id : 0, $this->object . '_image' => $post[$this->object . '_image']]);
			}

			list($post, $post_id, $this->type) = Event :: trigger(__CLASS__,__FUNCTION__, $post, $post_id, $this->type);

			if ($new) {
				$this->redirect(['module'=>$this->module, $this->object . '_id' => $id, 'type' => $this->type, 'success' => $message], [], false);
			}
		} else {
			$view->errors = $errors;
		}
	}
}
