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
use Vvveb\System\CacheManager;
use Vvveb\System\Event;

trait CommentTrait {
	private function insertComment($commentType = 'comment', $commentName = 'comment') {
		$result = false;
		$post   = &$this->request->post;

		if (isset($post['content'])) {
			//robots will also fill hidden inputs
			$notSpam =
			(isset($post['firstname-empty']) && empty($post['firstname-empty']) &&
			isset($post['lastname-empty']) && empty($post['lastname-empty']) &&
			isset($post['subject-empty']) && empty($post['subject-empty']));

			$user = $this->global['user'];

			if ($user) {
				$user['author'] = $user['display_name'];
			}

			$post['content'] = sanitizeHTML($post['content']);

			$comment   = array_merge($post, $user, [
				'created_at'  => date('Y-m-d H:i:s'),
				'status'      => 0,
				'notSpam'     => $notSpam,
				'commentType' => $commentType,
				'commentName' => $commentName,
			]);

			list($comment) = Event :: trigger(__CLASS__, __FUNCTION__ , $comment);

			if ($comment && $comment['notSpam']) {
				//$sql       = new \Vvveb\Sql\CommentSQL();
				$sql       = model($commentType);
				$result    = $sql->add([$commentType => $comment]);

				if ($result[$commentType]) {
					$comment["{$commentType}_id"] = $result[$commentType];

					$comments                                                  = $this->session->get('comments', []);
					$comments[$comment['slug']][$comment["{$commentType}_id"]] = $comment;
					$this->session->set($commentType, $comments);

					// clear notifications cache
					CacheManager :: clearObjectCache('component', 'notifications');

					$this->view->success[] = ucfirst($commentName) . __(' was posted!');
				} else {
					$this->view->errors[] = sprintf(__('Error adding %s!'), $text);
				}
			} else {
				//optional add comment with spam status
			}
		}

		return $result;
	}
}
