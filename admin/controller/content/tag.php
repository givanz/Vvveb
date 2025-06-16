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
use Vvveb\Sql\categorySQL;
use Vvveb\System\Images;

class Tag extends Base {
	protected $type = 'tag';

	function save() {
		$taxonomy_item_id = $this->request->get['taxonomy_item_id'] ?? false;
		$taxonomy_id      = $this->request->get['taxonomy_id'];
		$post_type        = $this->request->get['type'] ?? '';
		$tag              = $this->request->post['tag'] ?? false;

		if ($tag) {
			$tags   = new categorySQL();

			if ($taxonomy_item_id) {
				$result = $tags->editCategory(
					['taxonomy_item_content' => [$tag + $this->global],
						'taxonomy_item_id'      => $taxonomy_item_id,
						'taxonomy_item'         => ['taxonomy_item_id' => $taxonomy_item_id, 'taxonomy_id' => $taxonomy_id] + $tag,
					]);
			} else {
				$result = $tags->addCategory(
					['taxonomy_item_content' => $tag + $this->global,
						'taxonomy_item'         => ['taxonomy_id' => $taxonomy_id] + $tag,
						'site_id'               => $this->global['site_id'],
					]);
			}

			if ($result && isset($result['taxonomy_item_content'])) {
				$message                    = __('Tag saved!');
				$this->view->success['get'] = $message;

				if (! $taxonomy_item_id) {
					$this->redirect(['module'=> 'content/tag', 'taxonomy_item_id' => $result['taxonomy_item'], 'taxonomy_id' => $taxonomy_id, 'type' => $post_type, 'success' => $message], [], false);
				}
			} else {
				$this->view->errors[] = __('Error saving!');
			}
		}

		return $this->index();
	}

	function index() {
		$tags             = new categorySQL();
		$taxonomy_item_id = $this->request->get['taxonomy_item_id'] ?? false;
		$taxonomy_id      = $this->request->get['taxonomy_id'] ?? false;
		$post_type        = $this->request->get['type'] ?? '';

		$view            = $this->view;
		$admin_path      = \Vvveb\adminPath();
		$controllerPath  = $admin_path . 'index.php?module=media/media';
		$view->scanUrl   = "$controllerPath&action=scan";
		$view->uploadUrl = "$controllerPath&action=upload";

		$options = [
			'type'             => $this->type,
			'taxonomy_item_id' => $taxonomy_item_id,
			'taxonomy_id'      => $taxonomy_id,
		] + $this->global;
		unset($options['user_id']);

		$tag = $tags->getCategoryBySlug($options);

		if ($tag) {
			$tag['image_url'] = Images::image($tag['image'], 'tag', 'thumb');
		}

		$view->taxonomy_id = $taxonomy_id;
		$view->type        = $post_type;
		$view->status      = [1 => __('Enabled'), 0 => __('Disabled')];
		$view->tag         = $tag ?? [];
	}
}
