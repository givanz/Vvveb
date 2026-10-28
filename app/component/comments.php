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

namespace Vvveb\Component;

use function Vvveb\model;
use function Vvveb\session as sess;
use Vvveb\System\Component\ComponentBase;
use Vvveb\System\Event;
use Vvveb\System\Images;
use function Vvveb\url;

class Comments extends ComponentBase {
	protected string $type = 'comment';

	protected string $route = 'content/post/index';

	protected string $modelName = 'comment';

	public static array $defaultOptions = [
		'post_id'     => 'url',
		'user_id'     => null,
		'slug'        => 'url',
		'post_title'  => null, //include post title (for recent comments etc)
		'status'      => 1, //approved comments
		'language_id' => null,
		'start'       => 0,
		'limit'       => 10,
		'order'       => 'asc',
	];

	public int $cacheExpire = 0;

	/**
	 * Get comments sorted by parent hierarchy with avatars, URLs, and levels.
	 *
	 * @return array<string, mixed> Comments data with sorted comments, levels, and URLs.
	 */
	public function results(): array {
		$this->modelInstance         = model($this->modelName);
		$results                     = $this->modelInstance->getAll($this->options);
		$results[$this->type] ??= [];

		$order = $this->options['order'];

		if ($results && isset($results[$this->type])) {
			//sort comments by parent id and add child comments below parent
			uasort($results[$this->type], function($a, $b) use ($order) {
				//both root parents or both have same parent
				if (($a['parent_id'] == 0 && $b['parent_id'] == 0) ||
					($a['parent_id'] === $b['parent_id'])) {
					if ($order === 'desc') {
						return $b[$this->type . '_id'] <=> $a[$this->type . '_id'];
					} else {
						return $a[$this->type . '_id'] <=> $b[$this->type . '_id'];
					}
				}

				//both child comments with different parents
				if ($a['parent_id'] != 0 && $b['parent_id'] != 0) {
					return $a['parent_id'] <=> $b['parent_id'];
				}

				//a has parent bigger then b id
				if ($a['parent_id'] != 0) {
					return $a['parent_id'] <=> $b[$this->type . '_id'];
				}

				//a is root, b has parent
				return $a[$this->type . '_id'] <=> $b['parent_id'];
			});

			$level 	   = 0;
			$parent_id = 0;

			$language  = ['language' => $this->options['language']];

			if (self :: $global['default_language'] === self :: $global['language'] && !self :: $global['default_lang_slug']) {
				$language  = [];
			}

			foreach ($results[$this->type] as $id => &$comment) {
				if ($comment['parent_id'] == 0) {
					$level     = 0;
					$parent_id = 0;
				} else {
					if ($comment['parent_id'] != $parent_id) {
						if ($comment['parent_id'] > $parent_id) {
							$level++;
						} else {
							$level--;
						}
						$parent_id = $comment['parent_id'];
					}
				}

				if (isset($comment['avatar'])) {
					$comment['avatar_url'] = Images::image($comment['avatar'], 'user');
				}

				//rfc
				if (isset($comment['slug']) && $comment['slug']) {
					$comment['pubDate'] = date('r', strtotime($comment['created_at']));

					$anchor                = '#comment-' . $comment[$this->type . '_id'];

					//url
					$url                  = $language;

					if ($comment['type'] !== 'post' && $comment['type'] !== 'page') {
						$url['type'] = $comment['type'];
						$type        = 'post';
					}

					//if translation is missing slug is not available
					if (isset($comment['slug']) && $comment['slug']) {
						$url['slug'] = $comment['slug'];
					} else {
						$url['post_id'] = $comment['post_id'];
					}

					$comment['url']        =  url($this->route, $url) . $anchor;
					$comment['full-url']   =  url($this->route, $url + ['host' => SITE_URL, 'scheme' => $_SERVER['REQUEST_SCHEME'] ?? 'http']) . $anchor;
				}

				$comment['level']      =  $level;
			}
		}

		return $results;
	}

	/**
	 * Merge pending user comments from session into results.
	 *
	 * @param array<string, mixed> &$results Component results to modify.
	 * @param int                  $index    Request index.
	 *
	 * @return array<string, mixed> Modified results with pending comments merged.
	 */
	public function request(array &$results, int $index = 0): array {
		//check for user pending comments
		$slug            = $this->options['slug'] ?? false;
		$pendingComments = sess($this->type, []);

		if ($slug && $pendingComments && isset($pendingComments[$slug])) {
			$comments             = $pendingComments[$slug];
			$results[$this->type] += $comments;
			$results['count'] += count($comments);
		}

		//for component event convention use `results` as function
		[$results] = Event :: trigger(__CLASS__, 'results', $results);

		return $results;
	}
}
