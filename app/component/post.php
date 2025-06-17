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

use function Vvveb\__;
use function Vvveb\model;
use function Vvveb\sanitizeHTML;
use Vvveb\Sql\PostSQL;
use Vvveb\System\Component\ComponentBase;
use Vvveb\System\Core\Request;
use Vvveb\System\Event;
use Vvveb\System\Images;
use Vvveb\System\User\Admin;
use function Vvveb\url;

class Post  extends ComponentBase {
	public static $defaultOptions = [
		'post_id'        => 'url',
		'language_id'    => null,
		'site_id'        => null,
		'slug'           => 'url',
		'status'         => 'publish',
		'comment_count'  => 1,
		'comment_status' => 1,
		//'type' => 'post',
	];

	//called when fetching data, when cache expires
	function results() {
		$post = new PostSQL();

		$results = $post->get($this->options);

		//comments translations
		if (isset($results['comment_count'])) {
			$results['comment_text'] = sprintf(__('%d comment', '%d comments', (int)$results['comment_count']), $results['comment_count']);
		}

		if (isset($results['image'])) {
			$results['image'] = Images::image($results['image'], 'post');
		}

		if (isset($results['avatar'])) {
			$results['avatar'] = Images::image($results['avatar'], 'admin');
		}

		//comments translations
		$results['comment_count'] = $results['comment_count'] ?? 0;
		$results['comment_text']  = sprintf(__('%d comment', '%d comments', (int)$results['comment_count']), $results['comment_count']);

		//url
		$results['url']          = url('content/post/index', $results);
		$results['author-url']   = url('content/user/index', $results);
		$results['comments-url'] = $results['url'] . '#comments';

		//rfc
		$results['pubDate'] = date('r', strtotime($results['created_at']));
		$results['modDate'] = date('r', strtotime($results['updated_at']));

		list($results) = Event :: trigger(__CLASS__,__FUNCTION__, $results);

		return $results;
	}

	//called on each request
	function request(&$results, $index = 0) {
		$request    = Request::getInstance();
		$created_at = $request->get['created_at'] ?? ''; //revision preview

		if ($created_at && $results['post_id']) {
			//check if admin user to allow revision preview
			$admin = Admin::current();

			if ($admin) {
				$revisions = model('post_content_revision');
				$revision  = $revisions->get(['created_at' => $created_at, 'post_id' => $results['post_id'], 'language_id' => $results['language_id']]);

				if ($revision && isset($revision['content'])) {
					$results['content']    = $revision['content'];
					$results['created_at'] = $revision['created_at'];
				}
			}
		}

		return $results;
	}

	//called by editor on page save for each component on page
	//this method is called from admin app
	static function editorSave($id, $fields, $type = 'post') {
		$posts      = new PostSQL();
		$publicPath = \Vvveb\publicUrlPath() . 'media/';

		$post         = [];
		$post_content = [];

		foreach ($fields as $field) {
			$name  = $field['name'];
			$value = $field['value'];

			if ($name == 'content') {
				$post_content[$name] = sanitizeHTML($value);
			} else {
				if ($name == 'excerpt') {
					$post_content[$name] = sanitizeHTML($value);
				} else {
					if ($name == 'name') {
						$post_content[$name] =  strip_tags($value);
					} else {
						if ($name == 'image') {
							$value = str_replace($publicPath,'', $value);
						}
						$post[$name] = $value;
					}
				}
			}
		}

		if ($post_content) {
			$post_content['language_id'] = self :: $global['language_id'];
			//$post['post_content']['post_id'] = $id;
			$result = $posts->editContent(['post_content' => $post_content, 'post_id' => $id, 'language_id' => self :: $global['language_id']]);
		}

		if ($post || $post_content) {
			$post['post_id'] = $id;
			$result          = $posts->edit(['post' => $post, /* 'post_content' => [$post_content],*/ 'post_id' => $id]);
		}
	}
}
