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

class Product extends Base {
	public $type = 'product';

	private function insertComment() {
		$result    = false;
		$product   = &$this->request->product;

		if (isset($product['content'])) {
			//robots will also fill hidden inputs
			$notRobot =
			(isset($product['firstname-empty']) && empty($product['firstname-empty']) &&
			isset($product['lastname-empty']) && empty($product['lastname-empty']) &&
			isset($product['subject-empty']) && empty($product['subject-empty']));

			if ($notRobot) {
				$user = $this->global['user'];

				if ($user) {
					$user['author'] = $user['display_name'];
				}

				$product['content'] = sanitizeHTML($product['content']);

				$sql       = new \Vvveb\Sql\CommentSQL();
				$comment   = array_merge($product, $user, ['created_at' => date('Y-m-d H:i:s'), 'status' => 0]);
				$result    = $sql->add(['comment' => $comment]);

				if ($result['comment']) {
					$comment['comment_id'] = $result['comment'];

					$comments                                           = $this->session->get('comments', []);
					$comments[$comment['slug']][$comment['comment_id']] = $comment;
					$this->session->set('comments', $comments);

					$this->view->success[] = __('Comment was producted!');
				} else {
					$this->view->errors[] = __('Error adding comment!');
				}
			}
		}

		return $result;
	}

	function index() {
		if (isset($this->request->product['content'])) {
			$result = $this->insertComment();
		}

		$language = $this->request->get['language'] ?? '';
		$slug     = $this->request->get['slug'] ?? '';

		if ($slug) {
			$contentSql = new ProductSQL();
			$options    = $this->global + ['slug' => $slug, 'type' => $this->type];
			$content    = $contentSql->getContent($options);

			$error = __('Product not found!');

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

					$this->request->get['product_id']     = $languageContent['product_id'];
					$this->request->request['product_id'] = $languageContent['product_id'];
					$this->request->request['name']       = $languageContent['name'];

					if (isset($languageContent['template']) && $languageContent['template']) {
						$this->view->template($languageContent['template']);
						//force product template if a different html template is selected
						$this->view->tplFile("content/{$this->type}.tpl");
					}
				} else {
					$this->notFound(true, ['message' => $error, 'title' => $error]);
				}
			} else {
				$this->notFound(true, ['message' => $error, 'title' => $error]);
			}

			$this->view->product 	  = $languageContent;
			$this->view->content    = $content;
		}
	}
}
