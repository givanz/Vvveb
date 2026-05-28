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
use Vvveb\System\User\Admin;

class Comment extends Base {
	protected $type = 'comment';

	function save() {
		$type		     = $this->type;
		$comment_id = $this->request->get[$type . '_id'] ?? false;
		$comment    = $this->request->post[$type] ?? false;

		if ($comment) {
			$data = [$type => $comment, $type . '_id' => $comment_id];

			$editCapability = 'edit_other_posts';

			if ($this->type != 'comment') {
				$editCapability = 'edit_other_products';
			}

			if (Admin::hasCapability($editCapability)) {
				//unset($data['admin_id']);
			} else {
				$data['admin_id'] = $this->global['admin_id'];
			}

			$comments = model($type);

			if ($comment_id) {
				$result   = $comments->edit($data);
			} else {
				$result   = $comments->add([$type => $comment]);
			}

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

		$viewCapability = 'view_other_posts';
		if ($this->type != 'comment') {
			$viewCapability = 'view_other_products';
		}

		if (Admin::hasCapability($viewCapability)) {
			unset($options['admin_id']);
		} else {
			$options['admin_id'] = $this->global['admin_id'];
		}

		$this->view->$type = $comments->get($options);

		if (! $this->view->$type) {
			$this->notFound();
		}
	}
}
