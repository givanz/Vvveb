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
use function Vvveb\setLanguage;
use Vvveb\System\Event;

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

		if ($slug) {
			$contentSql = new ProductSQL();
			$options    = $this->global + ['product_id' => $product_id, 'slug' => $slug, /*'type' => $this->type, */'status' => 1];
			$content    = $contentSql->getContent($options);

			list($content, $language, $slug) = Event :: trigger(__CLASS__,__FUNCTION__, $content, $language, $slug);

			$error = __('Product not found!');

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

					$this->request->get['product_id']      = $languageContent['product_id'];
					$this->request->request['product_id']  = $languageContent['product_id'];
					$this->request->get['name']            = $languageContent['name'];
					$this->request->request['name']        = $languageContent['name'];
					$this->request->request['code']        = $languageContent['code'];
					$this->request->request['language_id'] = $languageContent['language_id'];

					if (isset($languageContent['template']) && $languageContent['template']) {
						$this->view->template($languageContent['template']);
						//force product template if a different html template is selected
						$this->view->tplFile("product/{$this->type}.tpl");
					}
				} else {
					$this->notFound(true, ['message' => $error, 'title' => $error]);
				}

				list($content, $languageContent, $language, $slug) = Event :: trigger(__CLASS__,__FUNCTION__ . ':after', $content, $languageContent, $language, $slug);
			} else {
				$this->notFound(true, ['message' => $error, 'title' => $error]);
			}

			$this->view->product 	  = $languageContent;
			$this->view->content    = $content;
		}
	}
}
