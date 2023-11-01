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
use function Vvveb\model;

class Comment extends Base {
	protected $type = 'comment';

	function save() {
		$type		     = $this->type;
		$comment_id = $this->request->get[$type . '_id'] ?? false;
		$comment    = $this->request->post[$type] ?? false;

		if ($comment_id && $comment) {
			$comments = model($type);
			$result   = $comments->edit([$type => $comment, $type . '_id' => $comment_id]);

			if ($result && isset($result[$type])) {
				$this->view->success[] = __('Saved!');
			} else {
				$this->view->errors[] = __('Error saving!');
			}
		}

		return $this->index();
	}

	function index() {
		$type		     = $this->type;
		$comments   = model($type);
		$comment_id = $this->request->get[$type . '_id'] ?? false;

		$options = [
			'type'        => $this->type,
			$type . '_id' => $comment_id,
		] + $this->global;
		unset($options['user_id']);

		$this->view->$type = $comments->get($options);
	}
}
