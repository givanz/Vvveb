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

namespace Vvveb\System\Traits;

use function Vvveb\__;
use Vvveb\System\Images;
use function Vvveb\url;

trait Post {
	function posts(&$posts, &$options = []) {
		foreach ($posts as &$post) {
			$post = $this->post($post, $options);
		}

		return $posts;
	}

	function post(&$post, &$options = []) {
		$type            = $options['type'] ?? 'post';

		if (isset($post['images'])) {
			$post['images'] = json_decode($post['images'], 1);

			foreach ($post['images'] as &$image) {
				$image = Images::image($image, 'post', $options['image_size'] ?? 'medium');
			}
		}

		foreach (['categories' => 'category', 'tags' => 'tag', 'taxonomy' => $options['taxonomy'] ?? ''] as $taxonomy => $route) {
			if (isset($post[$taxonomy])) {
				$post[$taxonomy] = json_decode($post[$taxonomy], 1);
				$count           = $options[$taxonomy];

				if (! $post[$taxonomy]) {
					continue;
				}

				if (is_numeric($count) && is_array($post[$taxonomy])) {
					$post[$taxonomy] = array_slice($post[$taxonomy], 0, $count);
				}

				foreach ($post[$taxonomy] as &$cat) {
					$cat['url'] = url("content/$route/index", $cat);
				}
			}
		}

		if (isset($post['image'])) {
			$post['image'] = $post['images'][] = Images::image($post['image'], 'post', $options['image_size'] ?? 'medium');
		}

		if (isset($post['avatar'])) {
			$post['avatar'] = $post['avatar'] = Images::image($post['avatar'], 'admin');
		}

		if (empty($post['excerpt']) && ! empty($post['content'])) {
			$post['excerpt'] = substr(strip_tags($post['content']), 0, $options['excerpt_limit'] ?? 250);
		}

		//comments translations
		$post['comment_text'] = sprintf(__('%d comment', '%d comments', (int)$post['comment_count']), $post['comment_count']);

		//date formatting that can be used for url parameters
		$date = date_parse($post['created_at']);

		foreach (['year', 'day', 'month', 'hour', 'minute'] as $key) {
			$post[$key] = $date[$key] ?? '';
		}

		$language = [];

		if ($post['language_id'] != $options['default_language_id']) {
			$language = ['language' => $options['language']];

			if (! $post['name']) {
				$post['name']   = '[' . __('No translation') . ']';
				$post['slug']   = 'no-translation';
			}
		}

		//rfc
		$post['pubDate'] = date('r', strtotime($post['created_at']));
		$post['modDate'] = date('r', strtotime($post['updated_at']));
		$post['lastMod'] = date('Y-m-d\TH:i:sP', strtotime($post['updated_at']));

		//url
		$url                  =  ['slug' => $post['slug'], 'post_id' => $post['post_id']] + $language;
		$post['url']          = url("content/$type/index", $url);
		$post['full-url']     = url("content/$type/index", $url + ['host' => SITE_URL, 'scheme' => $_SERVER['REQUEST_SCHEME'] ?? 'http']);
		$post['author-url']   = url('content/user/index', $post);
		$post['comments-url'] = $post['url'] . '#comments';

		return $post;
	}
}
