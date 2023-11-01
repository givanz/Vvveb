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

namespace Vvveb\Controller\User;

use function Vvveb\__;
use Vvveb\Controller\Base;
use function Vvveb\model;
use Vvveb\System\Images;

class Users extends Base {
	protected $type = 'user';

	protected $module = 'user/users';

	function delete() {
		$type       = $this->type;
		$user_id    = $this->request->post["{$type}_id"] ?? $this->request->get["{$type}_id"] ?? false;

		if ($user_id) {
			if (is_numeric($user_id)) {
				$user_id = [$user_id];
			}

			$users        = model($type);
			$options      = ["{$type}_id" => $user_id] + $this->global;
			$result       = $users->delete($options);

			if ($result && isset($result[$type])) {
				$this->view->success[] = sprintf(__('%s(s) deleted!'), ucfirst(__($type)));
			} else {
				$this->view->errors[] = sprintf(__('Error deleting %s!'), __($type));
			}
		}

		return $this->index();
	}

	function index() {
		$type         = $this->type;
		$view         = $this->view;
		$users        = model($type);
		$this->filter = $this->request->get['filter'] ?? [];

		$options    =  [
			'type'         => $this->type,
		] + $this->global + $this->filter;

		$results = $users->getAll($options);

		if ($results[$type]) {
			foreach ($results[$type] as $id => &$user) {
				$user['status_text']      = $user['status'] == '1' ? __('active') : __('inactive');
				$user['image']            = Images::image($type, $user['image'] ?? '');
				$user['delete-url']       = \Vvveb\url(['module' => $this->module, 'action' => 'delete'] + ["{$type}_id[]" => $user["{$type}_id"]]);
			}
		}

		$view->filter = $this->filter;
		$view->status = [1 => 'Active', 0 => 'Inactive'];
		$view->users  = $results[$type];
		$view->count  = $results['count'];
		$view->limit  = $options['limit'];
	}
}
