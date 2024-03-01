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

namespace Vvveb\Controller\Settings;

use function Vvveb\__;
use Vvveb\Controller\Base;
use function Vvveb\humanReadable;
use Vvveb\System\Images;

class PostType extends Base {
	protected $type = 'post';

	function save() {
		$postType = $this->request->post['post_type'] ?? false;
		$userType = $this->request->get['type'] ?? false;

		if ($postType) {
			$userPostTypes = \Vvveb\getSetting($this->type, 'types', []);

			if ($userType) {
				//edit
				if (isset($userPostTypes[$userType])) {
					$userPostTypes[$userType] = $postType + $userPostTypes[$userType];

					if ($userType != $postType['type']) {
						$userPostTypes[$postType['type']] = $userPostTypes[$userType];
						unset($userPostTypes[$userType]);
					}
				} else {
					return $this->notFound();
				}
			} else {
				//new
				$userPostTypes[$postType['type']] = $postType;
			}

			$userPostTypes = \Vvveb\setSetting($this->type, 'types', $userPostTypes);

			$successMessage        = humanReadable(__($this->type)) . ' ' . __('type saved!');
			$this->view->success[] = $successMessage;

			if ($userType != $postType['type']) {
				$this->session->set('success', $successMessage);
				$this->redirect(['module' => "settings/{$this->type}-type", 'type' => $postType['type']]);
			}
		}

		return $this->index();
	}

	function index() {
		$type                  = ucfirst($this->type);
		$userPostTypes         = \Vvveb\getSetting($this->type, 'types', []);

		$userType   = $this->request->get['type'] ?? false;
		$customType = [];

		if ($userType && isset($userPostTypes[$userType])) {
			$customType = $userPostTypes[$userType];
		}

		if ($userType && ! $customType) {
			$this->notFound();
		}

		if (isset($customType['icon-img'])) {
			$customType['image_url'] = Images::image($customType['icon-img'], $type);
		}

		$view            = &$this->view;
		$admin_path      = \Vvveb\adminPath();
		$view->post_type = $customType;
		$controllerPath  = $admin_path . 'index.php?module=media/media';
		$view->scanUrl   = "$controllerPath&action=scan";
		$view->uploadUrl = "$controllerPath&action=upload";

		//$this->view->taxonomy_id = $taxonomy_item_id;
	}
}
