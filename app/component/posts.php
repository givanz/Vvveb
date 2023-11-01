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
use function Vvveb\availableLanguages;
use function Vvveb\get;
use Vvveb\Sql\PostSQL;
use Vvveb\System\Component\ComponentBase;
use Vvveb\System\Event;
use Vvveb\System\Images;
use function Vvveb\url;

class Posts extends ComponentBase {
	public static $defaultOptions = [
		'page'                => ['url', 1],
		'post_id'             => 'url',
		'language_id'         => null,
		'source'              => 'autocomplete',
		'type'                => 'post',
		'site_id'             => null,
		'start'               => null,
		'limit'               => ['url', 8],
		'order_by'            => 'sort_order',
		'direction'           => ['desc', 'asc'],
		'status'              => 'publish',
		'excerpt_limit'   	  => 200,
		'comment_count'   	  => 1,
		'comment_status'   	  => 1,
		'taxonomy_item_id'    => NULL,
		'taxonomy_item_slug'  => NULL,
		'search'              => NULL,
		'admin_id'            => NULL,
		//archive
		'month'               => NULL,
		'year'                => NULL,
		'image_size' 		  => 'medium',
		'categories'		  => null,
		'tags' 		 		  => null,
		'taxonomy' 		      => null,
	];

	public $options = [];

	function __construct($options) {
		parent::__construct($options);

		$module = \Vvveb\getModuleName();

		switch ($module) {
			case 'content/post':
			break;

			case 'content/category':
				if ($this->options['taxonomy_item_id'] == 'page') {
					$this->options['taxonomy_item_id'] = 54;
				}

				if ($this->options['taxonomy_item_slug'] == 'page') {
					$this->options['taxonomy_item_slug'] = get('slug');
				}

			break;
		}
	}

	function results() {
		$posts = new PostSQL();

		if ($page = $this->options['page']) {
			$this->options['start'] = ($page - 1) * $this->options['limit'];
		}

		if ($this->options['limit'] && ! $page) {
			$this->options['start'] = 0;
		}

		if (isset($this->options['post_id']) && is_array($this->options['post_id']) && $this->options['source'] == 'autocomplete') {
			$this->options['post_id'] = array_keys($this->options['post_id']);
		} else {
			$this->options['post_id'] = [];
		}

		if (isset($this->options['order_by']) &&
				! in_array($this->options['order_by'], ['created_at', 'updated_at'])) {
			unset($this->options['order_by']);
		}

		if (isset($this->options['direction']) &&
				! in_array($this->options['direction'], ['asc', 'desc'])) {
			unset($this->options['direction']);
		}

		$results = $posts->getAll($this->options);
		//$languages = availableLanguages();

		if ($results && isset($results['posts'])) {
			foreach ($results['posts'] as $id => &$post) {
				if (isset($post['images'])) {
					$post['images'] = json_decode($post['images'], 1);

					foreach ($post['images'] as &$image) {
						$image = Images::image($image, 'post', $this->options['image_size']);
					}
				}

				foreach (['categories' => 'category', 'tags' => 'tag', 'taxonomy' => $this->options['taxonomy'] ?? ''] as $taxonomy => $route) {

					if (isset($post[$taxonomy])) {
						$post[$taxonomy] = json_decode($post[$taxonomy], 1);
						$count           = $this->options[$taxonomy];
						if (!$post[$taxonomy]) continue;
						if (is_numeric($count) && is_array($post[$taxonomy])) {
							$post[$taxonomy] = array_slice($post[$taxonomy], 0, $count);
						}

						foreach ($post[$taxonomy] as &$cat) {
							$cat['url'] = url("content/$route/index", $cat);
						}
					}
				}

				if (isset($post['image'])) {
					$post['image'] = $post['images'][] = Images::image($post['image'], 'post', $this->options['image_size']);
				}

				if (empty($post['excerpt']) && ! empty($post['content'])) {
					$post['excerpt'] = substr(strip_tags($post['content']), 0, $this->options['excerpt_limit']);
				}

				//comments translations
				$post['comment_text'] = sprintf(__('%d comment', '%d comments', (int)$post['comment_count']), $post['comment_count']);

				//date formatting that can be used for url parameters
				$date = date_parse($post['created_at']);

				foreach (['year', 'day', 'month', 'hour', 'minute'] as $key) {
					$post[$key] = $date[$key] ?? '';
				}

				$language = [];

				if ($post['language_id'] != $this->options['default_language_id']) {
					$language = ['language' => $this->options['language']];
				}

				//rfc
				$post['pubDate'] = date('r', strtotime($post['created_at']));

				//url
				$url                  =  ['slug' => $post['slug'], 'post_id' => $post['post_id']] + $language;
				$post['url']          = url('content/post/index', $url);
				$post['full-url']     = url('content/post/index', $url + ['host' => SITE_URL, 'scheme' => $_SERVER['REQUEST_SCHEME'] ?? 'http']);
				$post['author-url']   = url('content/user/index', $post);
				$post['comments-url'] = $post['url'] . '#comments';
			}
		}

		//if archive then pass year and month
		if (isset($this->options['year'])) {
			$results['year'] = $this->options['year'];
		}

		if (isset($this->options['month'])) {
			$results['month'] = $this->options['month'];
		}

		$results['limit']  = $this->options['limit'];
		$results['start']  = $this->options['start'];
		$results['search'] = $this->options['search'];

		list($results) = Event :: trigger(__CLASS__,__FUNCTION__, $results);

		return $results;
	}
}
