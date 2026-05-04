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

namespace Vvveb\Controller\Product;

use \Vvveb\Sql\ProductSQL;
use function Vvveb\__;
use Vvveb\Controller\Base;
use Vvveb\Controller\Content\CommentTrait;
use function Vvveb\model;
use function Vvveb\postTypes;
use Vvveb\System\Core\FrontController;
use Vvveb\System\Event;
use Vvveb\System\Locale;
use Vvveb\System\User\Admin;
use function Vvveb\truncateWords;

class Product extends Base {
	public $type = 'product';

	use CommentTrait;

	function addReview() {
		return $this->index();
	}

	function addQuestion() {
		return $this->index();
	}

	function index() {
		if (isset($this->request->post['content'])) {
			if (isset($this->request->post['rating'])) {
				$result = $this->insertComment('product_review', __('review'));
			} else {
				$result = $this->insertComment('product_question', __('question'));
			}
		}

		$language   = $this->request->get['language'] ?? $this->global['language'] ?? $this->global['default_language'];
		$product_id = $this->request->get['product_id'] ?? '';
		$slug       = $this->request->get['slug'] ?? '';
		$type       = $this->request->get['type'] ?? '';
		$created_at = $this->request->get['created_at'] ?? ''; //revision preview
		$content    = [];
		$languageContent = [];

		//check for custom post or product
		if ($type) {
			$class     = '';
			$postTypes = postTypes('product');

			if (isset($postTypes[$type])) {
			} else {
				$postTypes = postTypes('post');

				if (isset($postTypes[$type])) {
					$class = 'Content';
					FrontController::redirect($class, 'index');

					die();
				} else {
					$error = sprintf(__('%s not found!'), ucfirst(__($this->type)));
					return $this->notFound(true, ['message' => $error, 'title' => $error]);
				}
			}
		}

		if ($product_id || $slug) {
			$contentSql = new ProductSQL();
			$options    = $this->global + ['product_id' => $product_id, 'slug' => $slug, 'type' => $type ?? $this->type, 'status' => 1];
			$content    = $contentSql->getContent($options);

			$class                           = __NAMESPACE__ . '\\' . ucfirst($this->type); //__CLASS__ is always Product
			list($content, $language, $slug) = Event :: trigger($class,__FUNCTION__, $content, $language, $slug);

			if ($content) {
				if (isset($content[$language])) {
					$languageContent = $content[$language];
				} else {
					if (isset($content[$this->global['language']])) {
						$languageContent = $content[$this->global['language']];
					} else {
						$languageContent = &$content[$this->global['default_language']] ?? [];
					}
				}

				if ($languageContent) {
					if ($this->global['language'] != $languageContent['code']) {
						Locale :: setLanguage($languageContent['code']);
					}

					$this->global['language']    = $languageContent['code'];
					$this->global['language_id'] = $languageContent['language_id'];

					//$this->session->set('language', $languageContent['code']);
					//$this->session->set('language_id', $languageContent['language_id']);

					$this->request->get['product_id']      = $languageContent['product_id'];
					$this->request->get['admin_id']        = $languageContent['admin_id'];
					$this->request->request['product_id']  = $languageContent['product_id'];
					$this->request->get['type']            = $languageContent['type'];
					$this->request->get['name']            = $languageContent['name'];
					$this->request->request['name']        = $languageContent['name'];
					$this->request->request['code']        = $languageContent['code'];
					$this->request->request['language_id'] = $languageContent['language_id'];

					if ($created_at) {
						//check if admin user to allow revision preview
						$admin = Admin::current();

						if ($admin) {
							$revisions = model('product_content_revision');
							$revision  = $revisions->get(['created_at' => $created_at, 'post_id' => $languageContent['post_id']] + $this->global);

							if ($revision && isset($revision['content'])) {
								$languageContent['content'] = $revision['content'];
							}
						}
					}

					if (isset($languageContent['template']) && $languageContent['template']) {
						$this->view->template($languageContent['template']);
						//force product template if a different html template is selected
						$this->view->tplFile("product/{$this->type}.tpl");
					}
				} else {
					$error = sprintf(__('%s not found!'), ucfirst(__($this->type)));
					return $this->notFound(true, ['message' => $error, 'title' => $error]);
				}

				$languageContent['title'] = $languageContent['name'];
				if (isset($this->global['site']['description']['title'])) {
					$languageContent['title'] = $languageContent['title'] . ' - ' . $this->global['site']['description']['title'];
				}

				//make sure title and desc don't exceed recommended seo limits
				$titleLen = strlen($languageContent['title']);
				if ($titleLen > 70) {
					$languageContent['title'] = truncateWords($languageContent['title'], 70);
				} else {
					if (isset($this->global['site']['description']['title']) &&
						($siteTitleLen = strlen($this->global['site']['description']['title'])) &&
						($titleLen + $siteTitleLen) < 70) {
						$languageContent['title'] = $languageContent['title'] . ' - ' . $this->global['site']['description']['title'];
					}
				}

				if ($languageContent['meta_description']) {
					$metaLen = strlen($languageContent['meta_description']);
					if ($metaLen > 70) {
						$languageContent['meta_description'] = truncateWords($languageContent['meta_description'], 160);
					}
				} else {
					$excerpt = ($languageContent['excerpt'] ?? '') ?: $languageContent['content'];
					$languageContent['meta_description'] = truncateWords(strip_tags($excerpt), 160);
				}

				list($content, $languageContent, $language, $slug) = Event :: trigger($class, __FUNCTION__ . ':after', $content, $languageContent, $language, $slug);
			} else {
				//check for custom post or product
				$class     = '';
				$postTypes = postTypes('post');

				if (isset($postTypes[$slug])) {
					$class = 'Content';
				} else {
					$postTypes = postTypes('product');

					if (isset($postTypes[$slug])) {
						$class = 'Product';
					}
				}

				if ($class) {
					$this->request->get['type'] = $slug;
					FrontController::redirect($class, 'index');

					die();
				} else {
					$error = sprintf(__('%s not found!'), ucfirst(__($this->type)));
					return $this->notFound(true, ['message' => $error, 'title' => $error]);
				}
			}

			$this->view->product = $languageContent;
			$this->view->content = $content;
		}
	}
}
