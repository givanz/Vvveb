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
use Vvveb\Controller\Listing;
use function Vvveb\humanReadable;
use Vvveb\Sql\PostSQL;
use Vvveb\System\Cache;
use Vvveb\System\Images;
use Vvveb\System\Locale;
use Vvveb\System\User\Admin;
use function Vvveb\url;

class Posts extends Listing {
	protected string $type = 'post';

	/**
	 * @var array<int, string> Additional modules to check permissions for (e.g. content/post/save).
	 */
	protected ?array $additionalPermissionCheck = ['content/post/save'];

	/**
	 * Initializes the controller, setting the post type from the request.
	 *
	 * @return string|null
	 */
	public function init(): ?string {
		if (isset($this->request->get['type'])) {
			$this->type = $this->request->get['type'];
		}

		return parent::init();
	}

	/**
	 * Duplicates an existing post with all its content, taxonomy items, and sites.
	 *
	 * @return void
	 */
	public function duplicate(): void {
		$post_id    = $this->request->post['post_id'] ?? false;

		if ($post_id) {
			$this->posts  = new PostSQL();
			$options      = ['post_id' => $post_id, 'type' => $this->type] + $this->global;

			$editCapability = 'edit_other_posts';

			if (Admin::hasCapability($editCapability)) {
				unset($options['admin_id']);
			}

			$data         = $this->posts->get($options);

			if (!$data) {
				$this->view->errors[] = sprintf(__('%s not found!'), ucfirst($this->type));
				$this->index();

				return;
			}

			$old_id       = $data['post_id'];

			unset($data['post_id']);
			$id = random_int(1, 1000000);

			foreach ($data['post_content'] as &$content) {
				unset($content['post_id']);
				$content['name'] .= ' [' . __('duplicate') . ']';
				$content['slug'] .= '-' . __('duplicate') . "-$old_id-$id";
			}

			if (isset($data['post_to_taxonomy_item'])) {
				$taxonomy_item_id = [];

				foreach ($data['post_to_taxonomy_item'] as &$item) {
					$taxonomy_item_id[] = $item['taxonomy_item_id'];
				}
			}

			$site_id = [];

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

					$success = ucfirst($this->type) . ' ' . __('duplicated') . '!';
					$success .= sprintf(' <a href="%s">%s</a>', $url, __('Edit') . " {$this->type}");
					$this->view->success['get'] = $success;
					//$this->session->set('success', $success);
					//$this->redirect(['module' => 'content/posts', 'type' => $this->type], [], false);
				} else {
					$this->view->errors[] = sprintf(__('Error duplicating %s!'), $this->type);
				}
			}
		}

		$this->index();
	}

	/**
	 * Deletes one or more posts by ID.
	 *
	 * @return void
	 */
	public function delete(): void {
		$post_id    = $this->request->post['post_id'] ?? false;

		if ($post_id) {
			if (is_numeric($post_id)) {
				$post_id = [$post_id];
			}

			$this->posts   = new PostSQL();
			$options       = [
				'post_id' => $post_id, 'type' => $this->type,
			] + $this->global;

			$editCapability = 'edit_other_posts';

			if (Admin::hasCapability($editCapability)) {
				unset($options['admin_id']);
			}

			$result  = $this->posts->delete($options);

			if ($result && isset($result['post'])) {
				$this->view->success[] = ucfirst($this->type) . __(' deleted!');
			} else {
				$this->view->errors[] = sprintf(__('Error deleting %s!'), $this->type);
			}
		}

		$this->index();
	}

	/**
	 * Returns archived posts grouped by year/month.
	 *
	 * @param array<string, mixed> $options Query options.
	 *
	 * @return array<string, string> Archive entries keyed by year/month.
	 */
	public function archives(array $options): array {
		$options['interval'] = 'month';

		$archives = $this->posts->getArchives($options);

		$return = [];

		$df	 = false;

		if (class_exists('\IntlDateFormatter')) {
			$dt = new \DateTime();
			$df = new \IntlDateFormatter(Locale::getLanguage(), \IntlDateFormatter::NONE, \IntlDateFormatter::NONE, null, null, 'MMMM');
		}

		foreach ($archives['archives'] as $index => &$archive) {
			if (isset($archive['month'])) {
				$monthNum              = $archive['month'];
				//$dateObj               = \DateTime::createFromFormat('!m', $monthNum);
				//$monthName             = $dateObj->format('F');

				$archive['month_text'] = $monthNum;

				if ($df) {
					$dt->setDate(0, $archive['month'], 0);
					$archive['month_text'] = ucfirst(datefmt_format($df, $dt));
				} else {
					$archive['month_text'] = date('F', mktime(0, 0, 0, $monthNum, 1, $archive['year']));
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

	/**
	 * Lists all posts with filtering, archive links, and capability-based access control.
	 *
	 * @return void
	 */
	public function index(): void {
		$view        = $this->view;
		$this->posts = new PostSQL();

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

		//override admin only for users that can view other authors posts
		if (Admin::hasCapability('view_other_posts') && isset($this->filter['admin_id'])) {
			$options['admin_id'] = $this->filter['admin_id'];
		}

		if (isset($this->request->get['filter']['site_id'])) {
			if ($this->request->get['filter']['site_id']) {
				//$options['site_id'] = $this->filter['site_id'];
			} else {
				unset($options['site_id']);
			}
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

				if (!$post['name'] && ($post['language_id'] != $this->global['default_language_id'])) {
					$post['name'] = '[' . __('No translation') . ']';
				}

				$url                   = ['module' => 'content/post', 'post_id' => $post['post_id'], 'type' => $post['type']];
				$adminPath             = \Vvveb\adminPath();
				$template              = $post['template'] ?: $defaultTemplate;
				$post['url']           = url($url);
				$post['edit-url']      = $post['url'];
				$post['admin-url']     = url(['module' => 'content/posts']) . '&type=' . $this->type . '&filter[admin_id_text]=' . $post['username'] . ' &filter[admin_id]=' . $post['admin_id'];
				$post['delete-url']    = url(['module' => 'content/posts', 'action' => 'delete'] + $url); // + ['post_id[]' => $post['post_id']]);
				$post['duplicate-url'] = url(['module' => 'content/posts', 'action' => 'duplicate'] + $url); // + ['post_id' => $post['post_id']]);
				$post['view-url']      = url("content/{$this->type}/index", $post + $url + ['host' => $this->global['host'], 'path' => $this->global['path'] ? '/' . $this->global['path'] : null]);
				$relativeUrl           = url("content/{$this->type}/index", $post + $url);
				$post['design-url']    = url(['module' => 'editor/page-builder', 'name' => urlencode($post['name'] ?? ''), 'url' => $relativeUrl, 'template' => $template], false);
			}
		}

		//archives for filter
		$cache    = Cache::getInstance();
		$archives = $cache->cache(
			'posts',
			"archives.{$this->type}.{$this->global['site_id']}",
			function() use (&$options) {
				return $this->archives($options);
			},
			259200
		);

		$view->set($results);
		$view->status           = ['publish' => __('Publish'), 'pending' => __('Pending'), 'draft' => __('Draft'), 'private' => __('Private'), 'password' => __('Password'), 'scheduled' => __('Scheduled')];
		$view->archives         = $archives;
		$view->filter           = $this->filter;
		$view->limit            = $options['limit'];
		$view->type             = $this->type;
		$view->addUrl           = url(['module' => 'content/post', 'type' => $this->type]);
		$view->type_name        = humanReadable(__($this->type));
		$view->type_name_plural = humanReadable(__($view->type . 's'));
	}
}
