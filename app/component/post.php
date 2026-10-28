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
use function Vvveb\siteSettings;
use Vvveb\Sql\PostSQL;
use Vvveb\System\Component\ComponentBase;
use Vvveb\System\Core\Request;
use Vvveb\System\Event;
use Vvveb\System\Traits\Post as PostTrait;
use Vvveb\System\User\Admin;

class Post extends ComponentBase {
	use PostTrait;

	public static array $defaultOptions = [
		'post_id'        => 'url',
		'language_id'    => null,
		'site_id'        => null,
		'slug'           => 'url',
		'status'         => 'publish',
		'comment_count'  => 1,
		'comment_status' => 1,
		'type'           => null,
		'image_size'     => 'large',
		'image_resize'   => null,
	];

	public int $cacheExpire = 0;

	/**
	 * Get single post with content, images, and formatting.
	 *
	 * @return array<string, mixed> Post data with content, images, and metadata.
	 */
	public function results(): array {
		$post = new PostSQL();

		$results = $post->get($this->options);

		if (!isset($results['post_id'])) {
			return [];
		}

		if (!isset($this->options['date_format'])) {
			$site                         = siteSettings();
			$this->options['date_format'] = $site['date_format'] ?? 'human';
		}

		if ($results) {
			$this->post($results, $this->options);
		}

		[$results] = Event :: trigger(__CLASS__, __FUNCTION__, $results);

		return $results;
	}

	/**
	 * Handle password-protected posts and revision preview on each request.
	 *
	 * @param array<string, mixed> &$results Post data to modify.
	 * @param int                  $index    Request index.
	 *
	 * @return array<string, mixed> Modified post data.
	 */
	public function request(array &$results, int $index = 0): array {
		$created_at = $this->request->get['created_at'] ?? '';

		if (isset($results['password']) && $results['password']) {
			if (isset($this->request->post['password'])) {
				if ($results['password'] === $this->request->post['password']) {
					$results['password'] = '';

					return $results;
				} else {
					$results['content'] = '<p>' . __('Invalid password!') . '</p>';
					$results['image']   = '';

					return $results;
				}
			}

			$results['content']  = '';
			$results['image']    = '';
		}

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

	/**
	 * Save post content from editor on page save.
	 *
	 * @param int                              $id     Post ID.
	 * @param array<int, array<string, mixed>> $fields Field data from editor.
	 * @param string                           $type   Post type identifier.
	 *
	 * @return void
	 */
	public static function editorSave(int $id, array $fields, string $type = 'post'): void {
		$posts      = new PostSQL();
		$publicPath = \Vvveb\publicUrlPath() . 'media/';

		$post         = [];
		$post_content = [];

		foreach ($fields as $field) {
			$name  = $field['name'];
			$value = $field['value'];

			if ($name === 'content') {
				$post_content[$name] = sanitizeHTML($value);
			} else {
				if ($name === 'excerpt') {
					$post_content[$name] = sanitizeHTML($value);
				} else {
					if ($name === 'name') {
						$post_content[$name] =  strip_tags($value);
					} else {
						if ($name === 'image') {
							$value = str_replace($publicPath, '', $value);
						}
						$post[$name] = $value;
					}
				}
			}
		}

		if ($post_content) {
			$post_content['language_id'] = self :: $global['language_id'];
			$result                      = $posts->editContent(['post_content' => $post_content, 'post_id' => $id, 'language_id' => self :: $global['language_id']]);
		}

		if ($post || $post_content) {
			$post['post_id'] = $id;
			$result          = $posts->edit(['post' => $post, 'post_content' => [], 'post_id' => $id]);
		}
	}
}
