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

use function Vvveb\availableLanguages;
use function Vvveb\get;
use Vvveb\Sql\PostSQL;
use Vvveb\System\Component\ComponentBase;
use Vvveb\System\Event;
use Vvveb\System\Traits\Post;

class Posts extends ComponentBase {
	use Post;

	public static $defaultOptions = [
		'page'               => ['url'],
		'post_id'            => 'url',
		'language_id'        => null,
		'source'             => 'autocomplete',
		'type'               => 'post',
		'site_id'            => null,
		'start'              => null,
		'limit'              => ['url', 8],
		'order_by'           => 'post_id',
		'direction'          => 'desc',
		'status'             => 'publish',
		'excerpt_limit'      => 200,
		'comment_count'      => 1,
		'comment_status'     => 1,
		'taxonomy_item_id'   => NULL,
		'taxonomy_item_slug' => NULL,
		'search'             => NULL,
		'search_boolean'     => true,
		'like'               => NULL,
		'admin_id'           => NULL,
		//archive
		'month'              => NULL,
		'year'               => NULL,
		'image_size'         => 'medium',
		'categories'         => null,
		'tags'               => null,
		'taxonomy'           => null,
		'username'           => null,
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

		if (! $this->options['page'] && ! $this->options['start']) {
			$this->options['page'] = 1;
		}

		if ($page = $this->options['page']) {
			$this->options['start'] = ($page - 1) * ((int) ($this->options['limit'] ?? 4));
		}
		/*
		if ($this->options['limit'] && ! $page) {
			$this->options['start'] = 0;
		}
		*/
		if (isset($this->options['post_id']) && is_array($this->options['post_id']) && $this->options['source'] == 'autocomplete') {
			$this->options['post_id'] = array_keys($this->options['post_id']);
		} else {
			$this->options['post_id'] = [];
		}

		if (isset($this->options['order_by']) &&
				! in_array($this->options['order_by'], ['post_id', 'admin_id', 'sort_order', 'parent', 'type', 'created_at', 'updated_at', 'slug', 'name'])) {
			unset($this->options['order_by']);
		}

		if (isset($this->options['direction']) &&
				! in_array($this->options['direction'], ['asc', 'desc'])) {
			unset($this->options['direction']);
		}

		if ($this->options['search'] && $this->options['search_boolean']) {
			$this->options['search'] .= '*';
		}

		//if only one taxonomy_item_id is provided then add it to array
		if (isset($this->options['taxonomy_item_id']) && ! is_array($this->options['taxonomy_item_id'])) {
			$this->options['taxonomy_item_id'] = [$this->options['taxonomy_item_id']];
		}

		$results = $posts->getAll($this->options);
		//$languages = availableLanguages();
		$this->options['type'] = $this->options['type'] ?: 'post';

		if ($results && isset($results['post'])) {
			$this->posts($results['post'], $this->options);
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
