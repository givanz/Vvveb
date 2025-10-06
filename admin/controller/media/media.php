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

namespace Vvveb\Controller\Media;

use function Vvveb\__;
use Vvveb\Controller\Base;
use function Vvveb\sanitizeFileName;
use Vvveb\Sql\media_ContentSQL;
use Vvveb\System\Traits\Media as MediaTrait;

class Media extends Base {
	use MediaTrait;

	protected $dirMedia = DIR_MEDIA;

	function index() {
		$adminPath      = \Vvveb\adminPath();
		$controllerPath = $adminPath . 'index.php?module=media/media';
		$this->setMediaEndpoints($controllerPath);

		$this->view->mediaContentUrl = "$controllerPath&action=mediaContent";
	}

	function mediaContentSave() {
		$file    = sanitizeFileName($this->request->post['file']);
		$content = ($this->request->post['media_content'] ?? []);
		$result  = false;

		if ($file && $content) {
			$mediaContent = new media_ContentSQL();
			$media        = $mediaContent->get(['file' => $file] + $this->global);

			if ($media) {
				$result = $mediaContent->edit(['media_id' => $media['media_id'], 'media_content' => $content, 'media' => []]);
			} else {
				$result = $mediaContent->add(['media' => ['file' => $file], 'media_content' => $content]);
			}
		}

		if ($result) {
			$message = ['success' => true, 'message' => __('Saved!')];
		} else {
			$message = ['success' => false, 'message' => __('Error saving!')];
		}

		$this->response->setType('json');
		$this->response->output($message);
	}

	function mediaContent() {
		$file        = sanitizeFileName($this->request->get['file']);
		$themeFolder = DIR_MEDIA;

		$mediaContent = new media_ContentSQL();
		$result       = $mediaContent->getContent(['file' => $file] + $this->global);

		$this->response->setType('json');
		$this->response->output($result ?? []);
	}
}
