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

use \Vvveb\Sql\PostSQL;
use function Vvveb\__;
use Vvveb\Controller\Base;
//use Vvveb\System\Component\Component;
use function Vvveb\model;
use function Vvveb\setLanguage;
use Vvveb\System\Event;
use Vvveb\System\User\Admin;

class Post extends Base {
	public $type = 'post';

	use CommentTrait;

	function addComment() {
		return $this->index();
	}

	function index() {
		if (isset($this->request->post['content'])) {
			$result = $this->insertComment();
		}

		$language   = $this->request->get['language'] ?? $this->global['language'] ?? $this->global['default_language'];
		$post_id    = $this->request->get['post_id'] ?? '';
		$slug       = $this->request->get['slug'] ?? '';
		$created_at = $this->request->get['created_at'] ?? ''; //revision preview

		if ($post_id || $slug) {
			$contentSql = new PostSQL();
			$options    = $this->global + ['post_id' => $post_id, 'slug' => $slug/*, 'type' => $this->type*/];
			$content    = $contentSql->getContent($options) ?? [];

			$class                           = __NAMESPACE__ . '\\' . ucfirst($this->type); //__CLASS__ is always Post
			$error                           = __('Post not found!');
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
						setLanguage($languageContent['code']);
					}

					$this->global['language']    = $languageContent['code'];
					$this->global['language_id'] = $languageContent['language_id'];

					//$this->session->set('language', $languageContent['code']);
					//$this->session->set('language_id', $languageContent['language_id']);

					$this->request->get['post_id']         = $languageContent['post_id'];
					$this->request->get['admin_id']        = $languageContent['admin_id'];
					$this->request->request['post_id']     = $languageContent['post_id'];
					$this->request->get['name']            = $languageContent['name'];
					$this->request->request['name']        = $languageContent['name'];
					$this->request->request['code']        = $languageContent['code'];
					$this->request->request['language_id'] = $languageContent['language_id'];

					if ($created_at) {
						//check if admin user to allow revision preview
						$admin = Admin::current();

						if ($admin) {
							$revisions = model('post_content_revision');
							$revision  = $revisions->get(['created_at' => $created_at, 'post_id' => $languageContent['post_id']] + $this->global);

							if ($revision && isset($revision['content'])) {
								$languageContent['content'] = $revision['content'];
							}
						}
					}

					if (isset($languageContent['template']) && $languageContent['template']) {
						$this->view->template($languageContent['template']);
						//force post template if a different html template is selected
						$this->view->tplFile("content/{$this->type}.tpl");
					}
				} else {
					return $this->notFound(true, ['message' => $error, 'title' => $error]);
				}

				list($content, $languageContent, $language, $slug) = Event :: trigger($class, __FUNCTION__ . ':after', $content, $languageContent, $language, $slug);
			} else {
				return $this->notFound(true, ['message' => $error, 'title' => $error]);
			}

			$this->view->post    = $languageContent;
			$this->view->content = $content;
		}
	}
}
