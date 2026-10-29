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
use Vvveb\Sql\Media_contentSQL;
use Vvveb\System\Traits\Media as MediaTrait;

/**
 * Handles media file management: listing, scanning, uploading, and media content editing.
 */
class Media extends Base {
	use MediaTrait;

	/**
	 * @var string Base directory for media files.
	 */
	protected string $dirMedia = DIR_MEDIA;

	/**
	 * Sets up the media manager view with scan/upload endpoints.
	 *
	 * @return void
	 */
	public function index(): void {
		$adminPath      = \Vvveb\adminPath();
		$controllerPath = $adminPath . 'index.php?module=media/media';
		$this->setMediaEndpoints($controllerPath);

		$this->view->mediaContentUrl = "$controllerPath&action=mediaContent";
	}

	/**
	 * Saves media file content (metadata) to the database.
	 *
	 * @return void
	 */
	public function mediaContentSave(): void {
		$file    = sanitizeFileName($this->request->post['file']);
		$content = ($this->request->post['media_content'] ?? []);
		$result  = false;

		if ($file && $content) {
			$mediaContent = new Media_contentSQL();
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

	/**
	 * Returns media file content (metadata) as JSON.
	 *
	 * @return void
	 */
	public function mediaContent(): void {
		$file        = sanitizeFileName($this->request->get['file']);

		$mediaContent = new Media_contentSQL();
		$result       = $mediaContent->getContent(['file' => $file] + $this->global);

		$this->response->setType('json');
		$this->response->output($result ?? []);
	}
}
