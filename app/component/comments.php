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
use function Vvveb\session;
use Vvveb\Sql\CommentSQL;
use Vvveb\System\Component\ComponentBase;
use Vvveb\System\Event;
use function Vvveb\url;

class Comments extends ComponentBase {
	protected $type = 'comment';

	protected $route = 'content/post/index';

	protected $model = 'comment';

	public static $defaultOptions = [
		'post_id'       => 'url',
		'slug'          => 'url',
		'post_title'    => NULL, //include post title (for recent comments etc)
		'status'        => 1, //approved comments
		'language_id'   => NULL,
		'start'         => 0,
		'limit'         => 10,
		'order'         => 'asc', //desc
	];

	//called when fetching data, when cache expires
	function results() {
		$comments             = model($this->model); //new CommentSQL();
		$results              = $comments->getAll($this->options);
		$results[$this->type] = $results[$this->type] ?? [];

		$order = $this->options['order'];

		if ($results && isset($results[$this->type])) {
			//sort comments by parent id and add child comments below parent
			//usort($results[$this->type], function ($a, $b) use ($order) {
			uasort($results[$this->type], function ($a, $b) use ($order) {
				//both root parents or both have same parent
				if (($a['parent_id'] == 0 && $b['parent_id'] == 0) ||
					($a['parent_id'] == $b['parent_id'])) {
					if ($order == 'desc') {
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

				//b has parent bigger then a id
				if ($b['parent_id'] != 0) {
					return $a[$this->type . '_id'] <=> $b['parent_id'];
				}

				return 0;
			});

			$level 	   = 0;
			$parent_id = 0;

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

				//rfc
				$comment['pubDate'] = date('r', strtotime($comment['created_at']));

				$anchor                = '#comment-' . $comment[$this->type . '_id'];
				$comment['url']   	    =  url($this->route, $comment) . $anchor;
				$comment['full-url']   =  url($this->route, $comment + ['host' => SITE_URL, 'scheme' => $_SERVER['REQUEST_SCHEME'] ?? 'http']) . $anchor;
				$comment['level']      =  $level;
			}
		}

		list($results) = Event :: trigger(__CLASS__,__FUNCTION__, $results);

		return $results;
	}

	//called on each request
	function request(&$results) {
		//check for user pending comments
		$slug            = $this->options['slug'] ?? false;
		$pendingComments = session($this->type, []);

		if ($slug && $pendingComments && isset($pendingComments[$slug])) {
			$comments             = $pendingComments[$slug];
			$results[$this->type] = $results[$this->type] + $comments;
			$results['count'] += count($comments);
		}

		return $results;
	}
}
