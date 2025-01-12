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

use function Vvveb\__;
use Vvveb\System\Traits\Listing as ListingTrait;

class Listing extends Base {
	use ListingTrait {
		ListingTrait::index as get;
	}

	function index() {
		$results = $this->get();

		$type = $this->type;

		if (isset($results[$type])) {
			$controller  	  = $this->controller ?? $type;
			$listController = $this->listController ?? $type;
			$type_id        = $this->type_id ?? "{$type}_id";
			$data_id        = $this->data_id ?? "{$type}_id";
			$list           = $this->list ?? $type;
			$module         = $this->module;

			foreach ($results[$type] as $id => &$row) {
				$params            = ['module' => "$module/$controller", $type_id => $row[$type_id]];
				$paramsList        = ['module' => "$module/$listController", $type_id => $row[$type_id]];
				$row['url']        = \Vvveb\url($params);
				$row['edit-url']   = \Vvveb\url($params);
				$row['delete-url'] = \Vvveb\url($paramsList + ['action' => 'delete', $type_id . '[]' => $row[$type_id]]);
			}
		}

		$this->view->{$this->list}  = $results[$this->type] ?? [];

		$this->view->status = [0 => __('Disabled'), 1 => __('Enabled')];
		$this->view->filter = $this->filter;
		$this->view->count  = $results['count'] ?? 0;
		$this->view->limit  = $results['limit'] ?? 0;
	}
}
