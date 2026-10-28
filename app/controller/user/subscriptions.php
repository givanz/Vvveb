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

use function Vvveb\model;
use Vvveb\System\User\User;

class Subscriptions extends Base {
	/**
	 * Display the customer's subscription list.
	 *
	 * @return void
	 */
	public function index(): void {
		$user     = User::current();
		$userId   = $user['user_id'] ?? 0;

		$model    = model('subscription');
		$page     = max((int)($this->request->get['page'] ?? 1), 1);
		$limit    = (int)($this->request->get['limit'] ?? 10);
		$start    = ($page - 1) * $limit;

		$results = $model->getAll([
			'user_id'     => $userId,
			'language_id' => $this->global['language_id'] ?? 1,
			'site_id'     => $this->global['site_id'] ?? 1,
			'start'       => $start,
			'limit'       => $limit,
		]);

		$this->view->subscriptions    = $results['subscription'] ?? [];
		$this->view->count            = $results['count'] ?? 0;
		$this->view->page             = $page;
		$this->view->limit            = $limit;
	}
}
