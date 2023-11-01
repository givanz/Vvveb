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
use function Vvveb\model;
use Vvveb\Sql\commentSQL;
use Vvveb\System\CacheManager;
use Vvveb\System\Core\View;
use Vvveb\System\Images;

class Comments extends Listing {
	protected $type = 'comment';

	protected $list = 'comments';

	protected $module = 'content';

	protected $listController = 'comments';

	function status() {
		$type       = $this->type;
		$comment_id = $this->request->get[$type . '_id'] ?? false;
		$status     = $this->request->get['newstatus'] ?? false;

		if ($comment_id) {
			$comments = model($type);

			if ($comments->edit([$type => ['status' => (int) $status], $type . '_id' => $comment_id])) {
				//CacheManager::delete($type);
				CacheManager::delete();
				$this->view->success[] = sprintf(__('%s status changed!'), humanReadable(__($type)));
			}
		}

		return $this->index();
	}

	function index() {
		$view 		        = View :: getInstance();
		$type 		        = $this->type;
		$controller     = $this->controller ?? $type;
		$listController = $this->listController ?? $controller;
		$module 	       = $this->module;
		$list 		        = $this->list ?? $type . 's';
		$status         = $this->request->get['status'] ?? '';

		$comment_status = [
			0  => __('Pending'),
			1  => __('Approved'),
			2  => __('Spam'),
			3  => __('Trash'),
			-1 => __('All'),
		];

		$status   = $this->request->get['status'] ?? -1;
		//$comments = new commentSQL();
		$comments = model($type);

		$options = [
			'type'         => $type,
		] + $this->global;
		unset($options['user_id']);

		if ($status > -1) {
			$options['status'] = $status;
		}

		$results        = $comments->getAll($options);
		$results[$type] = $results[$type] ?? [];

		foreach ($results[$type] as $id => &$comment) {
			if (isset($comment['image'])) {
				$comment['image'] = Images::image($type, $comment['image']);
			}

			$url                    = ['module' => "$module/$listController", 'action' => 'status', 'status' => $status, $type . '_id' => $comment[$type . '_id']];
			$postUrl                = ['module' => "$module/$controller", $type . '_id' => $comment[$type . '_id']];
			$comment['edit-url']    = \Vvveb\url($postUrl);
			$comment['delete-url']  = \Vvveb\url(['module' => "$module/$list", 'action' => 'delete'] + $url);
			$comment['approve-url'] = \Vvveb\url(['newstatus' => 1] + $url);
			$comment['spam-url']    = \Vvveb\url(['newstatus' => 2] + $url);
			$comment['trash-url']   = \Vvveb\url(['newstatus' => 3] + $url);
		}

		$statuses                = "{$type}_status";
		$view->$list             = $results[$type];
		$view->count             = $results['count'];
		$view->$statuses         = $comment_status;
		$view->module            = $module;
		$view->controller        = $controller;

		$view->limit  = $options['limit'];
		$view->status = $status;
	}
}
