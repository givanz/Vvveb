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
use Vvveb\Sql\PostSQL;
use Vvveb\System\Component\ComponentBase;
use Vvveb\System\Event;
use Vvveb\System\Images;
use function Vvveb\url;

class Post  extends ComponentBase {
	public static $defaultOptions = [
		'post_id'             => 'url',
		'language_id'         => null,
		'site_id'             => null,
		'slug'                => 'url',
		'status'              => 'publish',
		'comment_count'   	   => 1,
		'comment_status'   	  => 1,
		//'type'        => 'post',
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

		//comments translations
		$results['comment_count'] = $results['comment_count'] ?? 0;
		$results['comment_text']  = sprintf(__('%d comment', '%d comments', (int)$results['comment_count']), $results['comment_count']);

		//url
		$results['url']          = url('content/post/index', $results);
		$results['author-url']   = url('content/user/index', $results);
		$results['comments-url'] = $results['url'] . '#comments';

		list($results) = Event :: trigger(__CLASS__,__FUNCTION__, $results);

		return $results;
	}

	//called on each request
	function request($results) {
	}

	//called by editor on page save for each component on page
	//this method is called from admin app
	static function editorSave($id, $fields, $type = 'post') {
		$posts      = new PostSQL();
		$publicPath = \Vvveb\publicUrlPath() . 'media/';

		$post_content = [];

		foreach ($fields as $field) {
			$name  = $field['name'];
			$value = $field['value'];

			if ($name == 'content') {
				$post_content[$name] = $value;
			} else {
				if ($name == 'excerpt') {
					$post_content[$name] = $value;
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
		//$post['post_content']['post_id'] = $id;
		$post_content['language_id'] = 1;
		$post['post_content'][]      = $post_content;
		$post['post_id']             = $id;

		$result = $posts->edit(['post' => $post, 'post_id' => $id]);
	}
}
