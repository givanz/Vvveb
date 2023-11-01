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
//use Vvveb\System\Component\Component;
use Vvveb\Controller\Base;
use function Vvveb\sanitizeHTML;

class Post extends Base {
	public $type = 'post';

	private function insertComment() {
		$result = false;
		$post   = &$this->request->post;

		if (isset($post['content'])) {
			//robots will also fill hidden inputs
			$notRobot =
			(isset($post['firstname-empty']) && empty($post['firstname-empty']) &&
			isset($post['lastname-empty']) && empty($post['lastname-empty']) &&
			isset($post['subject-empty']) && empty($post['subject-empty']));

			if ($notRobot) {
				$user = $this->global['user'];

				if ($user) {
					$user['author'] = $user['display_name'];
				}

				$post['content'] = sanitizeHTML($post['content']);

				$sql       = new \Vvveb\Sql\CommentSQL();
				$comment   = array_merge($post, $user, ['created_at' => date('Y-m-d H:i:s'), 'status' => 0]);
				$result    = $sql->add(['comment' => $comment]);

				if ($result['comment']) {
					$comment['comment_id'] = $result['comment'];

					$comments                                           = $this->session->get('comments', []);
					$comments[$comment['slug']][$comment['comment_id']] = $comment;
					$this->session->set('comments', $comments);

					$this->view->success[] = __('Comment was posted!');
				} else {
					$this->view->errors[] = __('Error adding comment!');
				}
			}
		}

		return $result;
	}

	function addComment() {
		return $this->index();
		//$result = $this->insertComment();
		//$this->response->setType('json');
		//$this->response->output($result);

		//return false;
	}

	function index() {
		if (isset($this->request->post['content'])) {
			$result = $this->insertComment();
		}

		$language = $this->request->get['language'] ?? '';
		$slug     = $this->request->get['slug'] ?? '';

		if ($slug) {
			$contentSql = new PostSQL();
			$options    = $this->global + ['slug' => $slug, 'type' => $this->type];
			$content    = $contentSql->getContent($options);

			$error = __('Post not found!');

			if ($content) {
				if (isset($content[$language])) {
					$languageContent = $content[$language];
				} else {
					if (isset($content[$this->global['language']])) {
						$languageContent = $content[$this->global['language']];
					} else {
						$languageContent = $content[$this->global['default_language']] ?? [];
					}
				}

				if ($languageContent) {
					$this->global['language']    = $languageContent['code'];
					$this->global['language_id'] = $languageContent['language_id'];

					$this->session->set('language', $languageContent['code']);
					$this->session->set('language_id', $languageContent['language_id']);

					$this->request->get['post_id']     = $languageContent['post_id'];
					$this->request->request['post_id'] = $languageContent['post_id'];
					$this->request->request['name']    = $languageContent['name'];

					if (isset($languageContent['template']) && $languageContent['template']) {
						$this->view->template($languageContent['template']);
						//force post template if a different html template is selected
						$this->view->tplFile("content/{$this->type}.tpl");
					}
				} else {
					$this->notFound(true, ['message' => $error, 'title' => $error]);
				}
			} else {
				$this->notFound(true, ['message' => $error, 'title' => $error]);
			}

			$this->view->post 	  = $languageContent;
			$this->view->content = $content;
		}
	}
}
