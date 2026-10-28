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
use function Vvveb\stripChars;
use Vvveb\System\CacheManager;
use Vvveb\System\Event;

trait CommentTrait {
	/**
	 * Insert a comment (or review/question) from the current POST data.
	 *
	 * @param string $commentType Model name for the comment type (e.g. 'comment', 'product_review').
	 * @param string $commentName Human-readable name used in success/error messages.
	 *
	 * @return array<string, mixed>|bool Insert result array or false on failure.
	 */
	private function insertComment(string $commentType = 'comment', string $commentName = 'comment'): array|bool {
		$result = false;
		$post   = &$this->request->post;

		$commentsOpen      = $this->global['site']['default_comment_status'] ?? false;
		$anonymousComments = $this->global['site']['anonymous_comments'] ?? false;

		if (!$commentsOpen || (!$anonymousComments && !$this->global['user'])) {
			return [];
		}

		if (isset($post['content'])) {
			//robots will also fill hidden inputs
			$notSpam =
			(isset($post['firstname-empty']) && empty($post['firstname-empty']) &&
			isset($post['lastname-empty']) && empty($post['lastname-empty']) &&
			isset($post['subject-empty']) && empty($post['subject-empty']));

			$user = $this->global['user'] ?? false;

			if ($user) {
				$post['author'] = $user['display_name'] ?? '';
				$post['email']  = $user['email'] ?? '';
			}

			//author, content and email are required for the comment to be stored
			foreach (['author', 'content', 'email'] as $field) {
				if (!isset($post[$field]) || trim((string)$post[$field]) === '') {
					$this->view->errors[] = __('Please fill in all required fields!');

					return false;
				}
			}

			//never persist the honeypot fields in the stored data
			foreach (['firstname-empty', 'lastname-empty', 'subject-empty'] as $spamField) {
				unset($post[$spamField]);
			}

			//sanitize html and inline js code
			$post['content'] = sanitizeHTML($post['content']);
			$post['author']  = sanitizeHTML(stripChars($post['author']));

			$commentData   = array_merge($post, is_array($user) ? $user : [], [
				'created_at'  => date('Y-m-d H:i:s'),
				'status'      => 0,
				'notSpam'     => $notSpam,
				'commentType' => $commentType,
				'commentName' => $commentName,
				'user_id'     => is_array($user) ? ($user['user_id'] ?? false) : false,
			]);

			[$comment] = Event::trigger(__CLASS__, __FUNCTION__, $commentData);

			if ($comment && $comment['notSpam']) {
				//$sql       = new \Vvveb\Sql\CommentSQL();
				$sql       = model($commentType);
				$result    = $sql->add([$commentType => $comment]);

				if ($result[$commentType]) {
					$comment["{$commentType}_id"] = $result[$commentType];

					$slug                                                      = $comment['slug'] ?? '';
					$comments                                                  = $this->session->get($commentType) ?? [];
					$comments[$slug][$comment["{$commentType}_id"]]            = $comment;
					$this->session->set($commentType, $comments);

					// clear notifications cache
					CacheManager::clearObjectCache('component', 'notifications');

					$this->view->success[] = ucfirst($commentName) . __(' was posted!');
				} else {
					$this->view->errors[] = sprintf(__('Error adding %s!'), $commentType);
				}
			} else {
				//optional add comment with spam status
			}
		}

		return $result;
	}
}
