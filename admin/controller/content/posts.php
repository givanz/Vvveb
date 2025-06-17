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
use Vvveb\System\User\Admin;
use function Vvveb\url;

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

	function duplicate() {
		$post_id    = $this->request->post['post_id'] ?? $this->request->get['post_id'] ?? false;

		if ($post_id) {
			$this->posts  = new PostSQL();
			$data         = $this->posts->get(['post_id' => $post_id, 'type' => $this->type]);
			$old_id       = $data['post_id'];

			unset($data['post_id']);
			$id = rand(1, 1000);

			foreach ($data['post_content'] as &$content) {
				unset($content['post_id']);
				$content['name'] .= ' [' . __('duplicate') . ']';
				$content['slug'] .= '-' . __('duplicate') . "-$old_id-$id";
			}

			if (isset($data['post_to_taxonomy_item'])) {
				foreach ($data['post_to_taxonomy_item'] as &$item) {
					$taxonomy_item_id[] = $item['taxonomy_item_id'];
				}
			}

			if (isset($data['post_to_site'])) {
				foreach ($data['post_to_site'] as &$item) {
					$site_id[] = $item['site_id'];
				}
			}

			if ($data) {
				$result = $this->posts->add([
					'post'             => $data,
					'post_content'     => $data['post_content'],
					'taxonomy_item_id' => $taxonomy_item_id ?? [],
					'site_id'          => $site_id,
				]);

				if ($result && isset($result['post'])) {
					$post_id = $result['post'];
					$url     = url(['module' => 'content/post', 'post_id' => $post_id, 'type' => $this->type]);

					$success = ucfirst($this->type) . __(' duplicated!');
					$success .= sprintf(' <a href="%s">%s</a>', $url, __('Edit') . " {$this->type}");
					$this->view->success[] = $success;
					$this->session->set('success', $success);
					$this->redirect(['module' => 'content/posts'], [], false);
				} else {
					$this->view->errors[] = sprintf(__('Error duplicating %s!'),  $this->type);
				}
			}
		}

		return $this->index();
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

		$return = [];

		$df	= false;

		if (class_exists('\IntlDateFormatter')) {
			$dt = new \DateTime();
			$df = new \IntlDateFormatter(\Vvveb\getLanguage(), \IntlDateFormatter::NONE, \IntlDateFormatter::NONE, NULL, NULL, 'MMMM');
		}

		foreach ($archives['archives'] as $index => &$archive) {
			if (isset($archive['month'])) {
				$monthNum              = $archive['month'];
				//$dateObj               = \DateTime::createFromFormat('!m', $monthNum);
				//$monthName             = $dateObj->format('F');

				$archive['month_text'] = $monthNum;

				if ($df) {
					$archive['month_text'] = ucfirst(datefmt_format($df, $dt));
					$dt->setDate(0, $archive['month'], 0);
				} else {
					$archive['month_text'] = date('F',mktime(0,0,0,$monthNum,1,$archive['year']));
				}
			}

			$name =
				(isset($archive['day']) ? $archive['day'] . ' ' : '') .
				(isset($archive['month']) ? $archive['month_text'] . ' ' : '') .
				(isset($archive['year']) ? $archive['year'] . ' ' : '');

			$archive['month'] = sprintf('%02d', $archive['month']);

			$return[$archive['year'] . '/' . $archive['month']] = $name;
		}
		/*
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
		*/
		return $return;
	}

	function index() {
		$view        = $this->view;
		$this->posts = new postSQL();

		$this->type   = $this->request->get['type'] ?? 'post';
		$this->filter = array_filter($this->request->get['filter'] ?? []);

		$options      =  [
			'type'          => $this->type,
			'comment_count' => true,
		] + $this->global;

		if (Admin::hasCapability('view_other_posts')) {
			unset($options['admin_id']);
		} else {
			$options['admin_id'] = $this->global['admin_id'];
		}

		$options += $this->filter;

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

		if ($results && isset($results['post'])) {
			foreach ($results['post'] as $id => &$post) {
				if (isset($post['image'])) {
					$post['image'] = Images::image($post['image'], 'post');
				}

				if (! $post['name'] && ($post['language_id'] != $this->global['default_language_id'])) {
					$post['name'] = '[' . __('No translation') . ']';
				}

				$url                   = ['module' => 'content/post', 'post_id' => $post['post_id'], 'type' => $post['type']];
				$adminPath             = \Vvveb\adminPath();
				$template              = $post['template'] ? $post['template'] : $defaultTemplate;
				$post['url']           = url($url);
				$post['edit-url']      = $post['url'];
				$post['admin-url']     = url(['module' => 'content/posts']) . '&filter[admin_id_text]=' . $post['username'] . ' &filter[admin_id]=' . $post['admin_id'];
				$post['delete-url']    = url(['module' => 'content/posts', 'action' => 'delete'] + $url + ['post_id[]' => $post['post_id']]);
				$post['duplicate-url'] = url(['module' => 'content/posts', 'action' => 'duplicate'] + $url + ['post_id' => $post['post_id']]);
				$post['view-url']      = url("content/{$this->type}/index", $post + $url + ['host' => $this->global['host']]);
				$relativeUrl           = url("content/{$this->type}/index", $post + $url);
				$post['design-url']    = url(['module' => 'editor/editor', 'name' => urlencode($post['name'] ?? ''), 'url' => $relativeUrl, 'template' => $template, 'host' => $this->global['host']], false);
			}
		}

		//archives for filter
		$cache    = Cache::getInstance();
		$archives = $cache->cache('posts',"archives.{$this->type}.{$this->global['site_id']}" ,
			function () use (&$options) {
				return $this->archives($options) ?? [];
			}, 259200);

		$view->set($results);
		$view->status           = ['publish' => 'Publish', 'pending' => 'Pending', 'draft' => 'Draft', 'private' => 'Private', 'password' => 'Password'];
		$view->archives         = $archives;
		$view->filter           = $this->filter;
		$view->limit            = $options['limit'];
		$view->type             = $this->type;
		$view->addUrl           = url(['module' => 'content/post', 'type' => $this->type]);
		$view->type_name        = humanReadable(__($this->type));
		$view->type_name_plural = humanReadable(__($view->type . 's'));
	}
}
