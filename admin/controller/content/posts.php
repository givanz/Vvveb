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
use Vvveb\Controller\Base;
use function Vvveb\humanReadable;
use Vvveb\Sql\PostSQL;
use Vvveb\System\Cache;
use Vvveb\System\Images;

class Posts extends Base {
	protected $type = 'post';

	//check for other modules permission like post and editor to enable links like save/delete etc
	protected $additionalPermissionCheck = ['content/post/save'];

	function init() {
		if (isset($this->request->get['type'])) {
			$this->type = $this->request->get['type'];
		}

		return parent::init();
	}

	function delete() {
		$post_id    = $this->request->post['post_id'] ?? $this->request->get['post_id'] ?? false;

		if ($post_id) {
			if (is_numeric($post_id)) {
				$post_id = [$post_id];
			}

			$this->posts   = new postSQL();
			$options       = [
				'post_id' => $post_id, 'type' => $this->type,
			] + $this->global;

			$result  = $this->posts->delete($options);

			if ($result && isset($result['post'])) {
				$this->view->success[] = ucfirst($this->type) . __(' deleted!');
			} else {
				$this->view->errors[] = sprintf(__('Error deleting %s!'),  $this->type);
			}
		}

		return $this->index();
	}

	function archives($options) {
		$options['interval'] = 'month';

		$archives = $this->posts->getArchives($options);

		$df	= false;

		if (class_exists('\IntlDateFormatter')) {
			$dt = new \DateTime();
			$df = new \IntlDateFormatter(\Vvveb\getLanguage(), \IntlDateFormatter::NONE, \IntlDateFormatter::NONE, NULL, NULL, 'MMMM');
		}

		$return = [];

		if (isset($archives['archives'])) {
			foreach ($archives['archives'] as $index => &$archive) {
				if (isset($archive['month'])) {
					$monthNum              = $archive['month'];

					$archive['month_text'] = $monthNum;

					if ($df) {
						$dt->setDate(0, $archive['month'], 0);
						$archive['month_text'] = ucfirst(datefmt_format($df, $dt));
					}
				}

				$archive['month'] = sprintf('%02d', $archive['month']);
				$name             =
				(isset($archive['month']) ? $archive['month_text'] . ' ' : '') .
				(isset($archive['year']) ? $archive['year'] . ' ' : '');

				$return[$archive['year'] . '/' . $archive['month']] = $name;
			}
		}

		return $return;
	}

	function index() {
		$view        = $this->view;
		$this->posts = new postSQL();

		$this->type   = $this->request->get['type'] ?? 'post';
		$this->filter = $this->request->get['filter'] ?? [];
		$options      =  [
			'type'          => $this->type,
			'comment_count' => true,
		] + $this->global + $this->filter;

		//override admin if admin_id filter set
		if (isset($this->filter['admin_id'])) {
			$options['admin_id'] = $this->filter['admin_id'];
		}

		if (isset($this->filter['archives']) && $this->filter['archives']) {
			$archives         = explode('/', $this->filter['archives']);
			$options['year']  = $archives[0];
			$options['month'] = $archives[1];
		}

		$results = $this->posts->getAll($options);

		$defaultTemplate = "content/{$this->type}.html";

		foreach ($results['posts'] as $id => &$post) {
			if (isset($post['image'])) {
				$post['image'] = Images::image($post['image'], 'post');
			}

			$url                = ['module' => 'content/post', 'post_id' => $post['post_id'], 'type' => $post['type']];
			$admin_path         = \Vvveb\adminPath();
			$template           = $post['template'] ? $post['template'] : $defaultTemplate;
			$post['url']        = \Vvveb\url($url);
			$post['edit-url']   = $post['url'];

			$post['admin-url']   =  \Vvveb\url(['module' => 'content/posts']) . '&filter[admin_id_text]=' . $post['username'] . ' &filter[admin_id]=' . $post['admin_id'];
			$post['delete-url']  = \Vvveb\url(['module' => 'content/posts', 'action' => 'delete'] + $url + ['post_id[]' => $post['post_id']]);
			$post['view-url']    =  \Vvveb\url("content/{$this->type}/index", $post);
			$post['design-url']  = $admin_path . \Vvveb\url(['module' => 'editor/editor', 'url' => $post['view-url'], 'template' => $template], false, false);
		}

		//archives for filter
		$cache    = Cache::getInstance();
		$archives = $cache->cache('posts',"archives.{$this->type}.{$this->global['site_id']}" ,
			function () use (&$options) {
				return $this->archives($options) ?? [];
			}, 259200);

		$view->set($results);
		$view->status           = ['publish' => 'publish', 'pending' => 'pending'];
		$view->archives         = $archives;
		$view->filter           = $this->filter;
		$view->limit            = $options['limit'];
		$view->type             = $this->type;
		$view->addUrl           = \Vvveb\url(['module' => 'content/post', 'type' => $this->type]);
		$view->type_name        = humanReadable(__($this->type));
		$view->type_name_plural = humanReadable(__($view->type . 's'));
	}
}
