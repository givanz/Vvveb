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

namespace Vvveb\Controller;

use function Vvveb\model;
use Vvveb\System\Traits\Crud as CrudTrait;
use Vvveb\System\Traits\Listing as ListingTrait;

class Listing extends Base {
	use ListingTrait, CrudTrait {
		ListingTrait::index as get;
		CrudTrait::delete insteadof ListingTrait;
	}

	function __construct() {
		$this->redirect = false;
		$this->fullPost = true;
	}

	function post() {
		$model = model($this->type);
		$data  = $this->request->post ?? [];

		if ($model) {
			$errors = $model->validate($data, 'add', $this->type);

			if ($errors) {
				$this->notFound(['errors' => $errors], 500);
			}
		}

		$this->redirect = false;
		$this->fullPost = true;

		return $this->save();
	}

	function index() {
		$results               = $this->get();
		$this->view->success[] = '%s(s) deleted!';

		if (isset($results['count'])) {
			$count = $results['count'] ?: 0;
			$limit = $results['limit'] ?: 0;
			$page  = $results['page'] ?: 1;
			//$pages = ($count && $limit) ? ceil($count / $limit) : 0;

			$this->response->addHeader('X-V-count', $count);
			$this->response->addHeader('X-V-limit', $limit);
			//$this->response->addHeader('X-V-page', $page);
			//$this->response->addHeader('X-V-pages', $pages);
		}

		return $results[$this->type] ?? [];
	}
}
