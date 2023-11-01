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
use Vvveb\System\CacheManager;
use Vvveb\System\Core\View;
use Vvveb\System\Images;
use Vvveb\System\Sites;
use Vvveb\System\Validator;

class Edit extends Base {
	protected $type = 'post';

	protected $object = 'post';

	protected $revisions = true;

	use TaxonomiesTrait, AutocompleteTrait;

	function getThemeFolder() {
		return DIR_THEMES . DS . Sites::getTheme() ?? 'default';
	}

	function index() {
		$view = $this->view;

		$admin_path          = \Vvveb\adminPath();
		$postOptions         = [];
		$post                = [];
		$post_id             = $this->request->get[$this->object . '_id'] ?? $this->request->post[$this->object . '_id'] ?? false;

		$controllerPath        = $admin_path . 'index.php?module=media/media';
		$view->scanUrl         = "$controllerPath&action=scan";
		$view->uploadUrl       = "$controllerPath&action=upload";
		$theme                 = Sites::getTheme() ?? 'default';
		$view->themeCss        = PUBLIC_PATH . "themes/$theme/css/admin-post-editor.css";
		//$view->themeCss        = PUBLIC_PATH . "themes/$theme/css/style.css";

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
			//get all languages
			//unset($options['language_id']);
			$post                = $posts->get($options);

			if (! $post) {
				$message = sprintf(__('%s not found!'), humanReadable(__($this->type)));
				$this->notFound(false, ['message' => $message, 'title' => $message]);
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
			$post['image_url'] = Images::image('',$this->object);
		}

		/*
		if (isset($post['updated_at'])) {
			$post['updated_at'] = str_replace(' ', 'T', $post['updated_at']);
		} else {
			$post['updated_at'] = date("Y-m-d\TH:i:s", isset($post['updated_at']) && $post['updated_at'] ? strtotime($post['updated_at']) : time());
		}*/

		$this->type = $post['type'] ?? $this->type;

		if ($this->object == 'product') {
			$route = "product/{$this->type}/index";
		} else {
			$route = "content/{$this->type}/index";
		}

		if ($this->revisions) {
			$revisions = model($this->object . '_content_revision');
		}

		if (isset($post[$this->object . '_content'])) {
			foreach ($post[$this->object . '_content'] as &$content) {
				if (! isset($post['url'])) {
					$post['url'] = \Vvveb\url($route, ['slug'=> $content['slug']]);
				}
				$language = [];

				if ($content['language_id'] != $this->global['default_language_id']) {
					foreach ($this->view->languagesList as $code => $lang) {
						if ($lang['language_id'] == $content['language_id']) {
							break;
						}
					}
					$language = ['language' => $code];
				}

				$content['url']             = \Vvveb\url($route, $content + $language);
				$content['revision_count']  = 0;

				if ($revisions) {
					$revision = $revisions->getAll([$this->object . '_id' => $post_id, 'language_id' => $content['language_id']]);

					if ($revision) {
						$content['revision_count'] = $revision['count'];
						$content['revision']       = $revision['revision'];
					}
				}
			}
		}

		$type_name  = humanReadable(__($this->type));

		$defaultTemplate = \Vvveb\getCurrentTemplate();
		$template        = isset($post['template']) && $post['template'] ? $post['template'] : $defaultTemplate;
		$themeFolder     = $this->getThemeFolder();

		if (isset($post['url'])) {
			$design_url         = $admin_path . \Vvveb\url(['module' => 'editor/editor', 'url' => $post['url'], 'template' => $template], false, false);
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

		$object                    = $this->object;
		$view->$object             = $post;
		$view->status              = ['publish' => 'Publish', 'draft' => 'Draft', 'pending' => 'Pending', 'private' => 'Private', 'password' => 'Password'];
		$view->templates           = \Vvveb\getTemplateList(false, ['email']);
		//$validator                 = new Validator([$this->object]);
		//$view->validatorJson       = $validator->getJSON();
		$view->type                = __($this->type);
		$view->type_name           = $type_name;
		$view->posts_list_url      =  \Vvveb\url(['module' => 'content/posts', 'type' => $this->type]);
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
							//$post['taxonomy_item'][] = $tagId;
						} else {
							//add new taxonomy_item
							$tagId = $this->addCategory($listId, $tag);
						}
						$post['taxonomy_item'][] = $tagId;
					}
				}
			}

			$post = $post + $this->global;

			$new = false;

			if ($post_id) {
				$post[$this->object . '_id']                     = (int)$post_id;
				$result                                          = $posts->edit([$this->object => $post, $this->object . '_id' => $post_id] + $this->global);

				if ($result >= 0) {
					$this->view->success[] = ucfirst($this->type) . ' ' . __('saved') . '!';

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

				$return                            = $posts->add([$this->object => $post] + $this->global);
				$id                                = $return[$this->object] ?? false;

				if (! $id) {
					$view->errors = [$posts->error];
				} else {
					CacheManager::delete($this->object);
					$this->request->get[$this->object . '_id'] = $id;
					$post_id                                   = $id;
					$new                                       = true;

					$message         = ucfirst($this->type) . ' ' . __('saved') . '!';
					$view->success[] = $message;
				}
			}

			if (isset($post[$this->object . '_image'])) {
				$productImage = $this->object . 'Image';

				foreach ($post[$this->object . '_image'] as &$image) {
					$image = str_replace($publicPath . 'media/','', $image);
				}

				$posts->$productImage([$this->object . '_id' => $post_id ? $post_id : 0, $this->object . '_image' => $post[$this->object . '_image']]);
			}

			if ($new) {
				$this->redirect(['module'=>$this->module, $this->object . '_id' => $id, 'type' => $this->type, 'success' => $message], [], false);
			}
		} else {
			$view->errors = $errors;
		}
	}
}
