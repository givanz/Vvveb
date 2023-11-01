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

class Revisions extends Base {
	protected $type = 'post';

	//check for other modules permission like revision and editor to enable links like save/delete etc
	//protected $additionalPermissionCheck = ['content/revision/save'];

	function merge() {
	}

	function init() {
		if (isset($this->request->get['type'])) {
			$this->type = $this->request->get['type'];
		}
		$this->post_id     = $this->request->get[$this->type . '_id'] ?? false;
		$this->language_id = $this->request->get['language_id'] ?? false;
		$this->created_at  = $this->request->get['created_at'] ?? false;
		$this->revisions   = model($this->type . '_content_revision');

		return parent::init();
	}

	function delete() {
		$view            = $this->view;
		$this->revisions = model($this->type . '_content_revision');

		$options      =  [
			$this->type . '_id'          => $this->post_id,
			'language_id'                => $this->language_id,
			'created_at'                 => $this->created_at,
		] + $this->global;

		
		$results = $this->revisions->delete($options);

		header('Content-type: application/json; charset=utf-8');
		echo json_encode($options);

		die();
	}

	function revision() {
		$view            = $this->view;

		$options      =  [
			$this->type . '_id'          => $this->post_id,
			'language_id'                => $this->language_id,
			'created_at'                 => $this->created_at,
		] + $this->global;

		$results = $this->revisions->get($options);

		header('Content-type: application/json; charset=utf-8');
		echo json_encode($results);

		die();
	}

	function index() {
		return;
		$this->post_id     = $this->request->get[$this->type . '_id'] ?? false;
		$this->language_id = $this->request->get['language_id'] ?? false;
		$this->created_at  = $this->request->get['created_at'] ?? false;

		$view            = $this->view;
		$revisions       = model($this->type . '_content_revision');

		$options      =  [
			'type'          => $this->type,
			'comment_count' => true,
		] + $this->global + $this->filter;

		$results = $this->revisions->getAll($options);

		foreach ($results['revisions'] as $id => &$revision) {
		}

		$view->set($results);
		$view->status           = ['publish' => 'publish', 'pending' => 'pending'];
		$view->archives         = $archives;
		$view->filter           = $this->filter;
		$view->limit            = $options['limit'];
		$view->type             = $this->type;
		$view->addUrl           = \Vvveb\url(['module' => 'content/revision', 'type' => $this->type]);
		$view->type_name        = humanReadable(__($this->type));
		$view->type_name_plural = humanReadable(__($view->type . 's'));
	}
}
