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
use function Vvveb\model;
use function Vvveb\sanitizeHTML;

trait CommentTrait {
	private function insertComment($commentType = 'comment', $text = 'comment') {
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

				//$sql       = new \Vvveb\Sql\CommentSQL();
				$sql       = model($commentType);
				$comment   = array_merge($post, $user, ['created_at' => date('Y-m-d H:i:s'), 'status' => 0]);
				$result    = $sql->add([$commentType => $comment]);

				if ($result[$commentType]) {
					$comment["{$commentType}_id"] = $result[$commentType];

					$comments                                                  = $this->session->get('comments', []);
					$comments[$comment['slug']][$comment["{$commentType}_id"]] = $comment;
					$this->session->set($commentType, $comments);

					$this->view->success[] = ucfirst($text) . __(' was posted!');
				} else {
					$this->view->errors[] = sprintf(__('Error adding %s!'), $text);
				}
			}
		}

		return $result;
	}
}
