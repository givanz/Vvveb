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
use Vvveb\System\User\Admin;

class Revisions extends Base {
	protected $type = 'post';

	protected $object = 'post';

	//check for other modules permission like revision and editor to enable links like save/delete etc
	protected $additionalPermissionCheck = ['content/revisions/save'];

	function merge() {
	}

	function init() {
		if (isset($this->request->get['type'])) {
			$this->type = $this->request->get['type'];
		}

		if (isset($this->request->get['object'])) {
			$this->object = $this->request->get['object'];
		}

		$this->revisions   = model($this->object . '_content_revision');
		$this->post        = model($this->object);

		$this->options = ['type' => $this->type];

		foreach ([$this->object . '_id', 'language_id', 'created_at'] as $param) {
			if (isset($this->request->get[$param])) {
				$this->options[$param] = $this->request->get[$param];
			}
		}

		return parent::init();
	}

	function delete() {
		$options = $this->options + $this->global;
		$editCapability = 'edit_other_posts';

		if ($this->object == 'product') {
			$editCapability = 'edit_other_products';
		}

		if (Admin::hasCapability($editCapability)) {
			unset($options['admin_id']);
		} else {
		}

		$results = $this->revisions->delete($options);

		if ($results) {
			$message[] = __('Revision deleted!');
		}

		$this->response->setType('json');
		$this->response->output($message);
	}

	function save() {
		$content = $this->request->post['content'] ?? false;

		if ($content) {
			$options = $this->options + $this->global;
			$editCapability = 'edit_other_posts';

			if ($this->object == 'product') {
				$editCapability = 'edit_other_products';
			}

			if (Admin::hasCapability($editCapability)) {
				unset($options['admin_id']);
			} else {
			}


			$result = $this->post->editContent(
				[$this->type . '_content' => ['content' => $content]] +
				$options
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
		$editCapability = 'view_other_posts';

		if ($this->object == 'product') {
			$editCapability = 'view_other_products';
		}

		if (Admin::hasCapability($editCapability)) {
			unset($this->options['admin_id']);
		} else {
		}

		$view     = $this->view;
		$results  = $this->revisions->get($this->options);
		$revision = [];

		if ($results && $results['content']) {
			foreach (['content', 'created_at', 'display_name'] as $key) {
				$revision[$key] = $results[$key];
			}
		}

		$this->response->setType('json');
		$this->response->output($revision);
	}

	function index() {
		if (! isset($this->options[$this->object . '_id'])) {
			$this->notFound();
		}


		$view           = $this->view;
		$modelName      = $this->object . '_content_revision';
		$revisions      = model($modelName);
		$this->options += $this->global;

		$editCapability = 'view_other_posts';

		if ($this->object == 'product') {
			$editCapability = 'view_other_products';
		}

		if (Admin::hasCapability($editCapability)) {
			unset($this->options['admin_id']);
		} else {
		}

		$allOptions     = $this->options;
		unset($allOptions['created_at']);

		$post      = $this->post->get($this->options); // post/product content

		if (! isset($post[$this->object . '_id'])) {
			return $this->notFound();
		}

		if (isset($post['image'])) {
			$post['image'] = $post['image'] = Images::image($post['image'], $this->object, 'thumb');
		}

		$revisions = $this->revisions->getAll($allOptions); // all post/product revisions
		$revision  = $this->revisions->get($this->options); // latest or selected revision

		$results = [
			'revisions' => $revisions[$modelName] ?? [],
			'revision'  => $revision ?? [],
			'post'      => $post ?? [],
		];

		$view->set($results);

		$view->limit            = $this->options['limit'];
		$view->type             = $this->type;
		$view->options          = $this->options;
		$view->revisionUrl      = \Vvveb\url([
			'module'              => 'content/revisions',
			'action'              => 'revision',
			'type'                => $this->type,
			'object'              => $this->object,
			$this->object . '_id' => $this->options[$this->object . '_id'], ]);
		$view->type_name        = humanReadable(__($this->type));
		$view->type_name_plural = humanReadable(__($view->type . 's'));
	}
}
