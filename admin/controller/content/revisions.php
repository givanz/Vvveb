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
use Vvveb\System\Images;

class Revisions extends Base {
	protected $type = 'post';

	//check for other modules permission like revision and editor to enable links like save/delete etc
	protected $additionalPermissionCheck = ['content/revisions/save'];

	function merge() {
	}

	function init() {
		if (isset($this->request->get['type'])) {
			$this->type = $this->request->get['type'];
		}

		$this->revisions   = model($this->type . '_content_revision');
		$this->post        = model($this->type);

		$this->options = ['type' => $this->type];

		foreach ([$this->type . '_id', 'language_id', 'created_at'] as $param) {
			if (isset($this->request->get[$param])) {
				$this->options[$param] = $this->request->get[$param];
			}
		}

		return parent::init();
	}

	function delete() {
		$results = $this->revisions->delete($this->options + $this->global);

		header('Content-type: application/json; charset=utf-8');

		die();
	}

	function save() {
		$content = $this->request->post['content'] ?? false;

		if ($content) {
			$result = $this->post->editContent(
				[$this->type . '_content' => ['content' => $content]] +
				$this->options +
				$this->global
			);

			if ($result) {
				$this->view->success[] = ucfirst($this->type) . ' ' . __('saved') . '!';
			} else {
				$view->errors = [$post->error];
			}
		}

		return $this->index();
	}

	function revision() {
		$view            = $this->view;
		$results         = $this->revisions->get($this->options);

		if ($results && isset($results['content'])) {
			echo $results['content'];
		}

		die();
	}

	function index() {
		$view      = $this->view;
		$revisions = model($this->type . '_content_revision');
		$this->options += $this->global;

		$revisions = $this->revisions->getAll($this->options); // all post/product revisions
		$revision  = $this->revisions->get($this->options); // latest or selected revision
		$post      = $this->post->get($this->options); // post/product content

		if (isset($post['image'])) {
			$post['image'] = $post['image'] = Images::image($post['image'], $this->type, 'thumb');
		}

		$results = [
			'revisions' => $revisions['revision'] ?? [],
			'revision'  => $revision ?? [],
			'post'      => $post ?? [],
		];

		$view->set($results);

		$view->limit            = $this->options['limit'];
		$view->type             = $this->type;
		$view->revisionUrl      = \Vvveb\url(['module' => 'content/revisions', 'action' => 'revision', 'type' => $this->type]);
		$view->type_name        = humanReadable(__($this->type));
		$view->type_name_plural = humanReadable(__($view->type . 's'));
	}
}
