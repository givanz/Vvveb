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
use function Vvveb\friendlyDate;
use Vvveb\System\Images;
use function Vvveb\url;

trait Post {
	/**
	 * Process an array of posts through the single-post formatter.
	 *
	 * @param list<array<string, mixed>> $posts   Array of post data arrays, passed by reference.
	 * @param array<string, mixed>       $options Formatting options (image_size, excerpt_limit, etc.).
	 *
	 * @return list<array<string, mixed>> The formatted posts array<array-key, mixed>.
	 */
	public function posts(array &$posts, array &$options = []): array {
		foreach ($posts as &$post) {
			$post = $this->post($post, $options);
		}

		return $posts;
	}

	/**
	 * Format a single post with image URLs, taxonomy links, dates, and URLs.
	 *
	 * @param array<string, mixed> $post    The raw post data array, passed by reference.
	 * @param array<string, mixed> $options Formatting options. Expected keys include:
	 *                                      - type (string): post type override.
	 *                                      - image_size (string): image size variant.
	 *                                      - image_resize (string): resize mode ('cs' = crop/scale).
	 *                                      - taxonomy (string): custom taxonomy route.
	 *                                      - categories (int): max categories to include.
	 *                                      - tags (int): max tags to include.
	 *                                      - excerpt_limit (int): max excerpt characters.
	 *                                      - date_format (string): 'human' or a PHP date format.
	 *                                      - default_language_id (int): fallback language ID.
	 *                                      - default_lang_slug (string): fallback language slug.
	 *                                      - language (string): current language slug.
	 *
	 * @return array<string, mixed> The formatted post data.
	 */
	public function post(array &$post, array &$options = []): array {
		if (!$post) {
			return $post;
		}

		$type = $post['type'] ?? $options['type'] ?? 'post';

		if (isset($post['images'])) {
			$post['images'] = json_decode($post['images'], true);

			if (is_array($post['images'])) {
				foreach ($post['images'] as &$image) {
					$image = Images::image($image, 'post', $options['image_size'] ?? 'medium', $options['image_resize'] ?? 'cs');
				}
			}
		}

		$taxonomies = ['categories' => 'category', 'tags' => 'tag', 'taxonomy' => $options['taxonomy'] ?? ''];
		foreach ($taxonomies as $taxonomy => $route) {
			if (isset($post[$taxonomy])) {
				$decoded         = json_decode($post[$taxonomy], true);
				$post[$taxonomy] = $decoded;
				$count           = $options[$taxonomy] ?? 0;

				if (!$post[$taxonomy]) {
					continue;
				}

				if (is_numeric($count) && is_array($post[$taxonomy])) {
					$post[$taxonomy] = array_slice($post[$taxonomy], 0, (int)$count);
				}

				foreach ($post[$taxonomy] as &$cat) {
					$cat['url'] = url("content/$route/index", $cat);
				}
			}
		}

		if (isset($post['image'])) {
			$post['image'] = $post['images'][] = Images::image($post['image'], 'post', $options['image_size'] ?? 'medium', $options['image_resize'] ?? 'cs');
		}

		if (isset($post['avatar'])) {
			$post['avatar_url'] = Images::image($post['avatar'], 'admin');
		}

		if (empty($post['excerpt']) && !empty($post['content'])) {
			$post['excerpt'] = substr(strip_tags($post['content']), 0, (int)($options['excerpt_limit'] ?? 250));
		}

		//comments translations
		if (isset($post['comment_count'])) {
			$post['comment_text']  = sprintf(__('%d comment', '%d comments', (int)$post['comment_count']), $post['comment_count']);
		} else {
			$post['comment_text']  = '';
			$post['comment_count'] = 0;
		}

		//date formatting that can be used for url parameters
		$date = [];
		if (isset($post['created_at']) && $post['created_at']) {
			$date = date_parse($post['created_at']);
		}

		foreach (['year', 'day', 'month', 'hour', 'minute'] as $key) {
			$post[$key] = $date[$key] ?? '';
		}

		$language = [];

		if (($post['language_id'] != $options['default_language_id'])) {
			$language = ['language' => $options['language']];

			if ($post['name'] === null && isset($post['post_content'][$options['default_language_id']])) {
				$langFallback        = $post['post_content'][$options['default_language_id']];
				$post['name']        = $langFallback['name'];
				$post['slug']        = $langFallback['slug'];
				$post['content']     = $langFallback['content'];
				$post['language_id'] = $langFallback['language_id'];
			} else {
				if (!$post['name']) {
					$post['name'] = '[' . __('No translation') . ']';
				}
			}
		} else {
			if ($options['default_lang_slug'] ?? '') {
				$language = ['language' => $options['language']];
			}
		}

		//rfc
		$createdTime = strtotime((string)($post['created_at'] ?? '')) ?: time();
		$updatedTime = strtotime((string)($post['updated_at'] ?? '')) ?: $createdTime;

		$post['pubDate'] = date('r', $createdTime);
		$post['modDate'] = date('r', $updatedTime);
		$post['lastMod'] = date('Y-m-d\TH:i:sP', $updatedTime);

		if (!isset($options['date_format']) || ($options['date_format'] ?? 'human') === 'human') {
			$post['created_at_formatted'] = friendlyDate($createdTime);
		} else {
			$post['created_at_formatted'] = date((string)$options['date_format'], $createdTime);
		}

		//url
		$url = $language;

		if ($type !== 'post' && $type !== 'page') {
			$url['type'] = $post['type'];
			$type        = 'post';
		}

		//if translation is missing slug is not available
		if (isset($post['slug']) && $post['slug']) {
			$url['slug'] = $post['slug'];
		} else {
			$url['post_id'] = $post['post_id'];
		}

		$post['url']          = url("content/$type/index", $url);
		$post['full-url']     = url("content/$type/index", $url + ['host' => SITE_URL, 'scheme' => $_SERVER['REQUEST_SCHEME'] ?? 'http']);
		$post['author-url']   = url('content/user/index', ['username' => $post['username'] ?? '']);

		$post['comments-url'] = $post['url'] . '#comments';

		return $post;
	}
}
