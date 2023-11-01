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
use function Vvveb\humanReadable;
use function Vvveb\model;
use Vvveb\System\Core\View;
use Vvveb\System\Images;

class Listing extends Base {
	protected $module = '';

	protected $type = '';

	function init() {
		if (isset($this->request->get['type'])) {
			//$this->type = $this->request->get['type'];
		}

		return parent::init();
	}

	function delete() {
		$type    = $this->type;
		$type_id = "{$type}_id";

		$data_id    = $this->request->post[$type_id] ?? $this->request->get[$type_id] ?? false;

		if ($data_id) {
			if (is_numeric($data_id)) {
				$data_id = [$data_id];
			}

			if (isset($this->model)) {
				$modelName = $this->model;
			} else {
				$modelName = $type;
			}

			$model               = model($modelName);
			$options             = [$type_id => $data_id] + $this->global;
			$result              = $model->delete($options);
			$name                = ucfirst(__($type));

			if ($result && isset($result[$type])) {
				$this->view->success[] = sprintf(__('%s(s) deleted!'), humanReadable($name));
			} else {
				$this->view->errors[] = sprintf(__('Error deleting %s!'), humanReadable($name));
			}
		}

		return $this->index();
	}

	function index() {
		$view          = View :: getInstance();

		$type           = $this->type;
		$controller  	  = $this->controller ?? $type;
		$listController = $this->listController ?? $type;
		$type_id        = "{$type}_id";
		$list           = $this->list;
		$module         = $this->module;
		$this->filter   = $this->request->get['filter'] ?? [];

		if (isset($this->model)) {
			$modelName = $this->model;
		} else {
			$modelName = $type;
		}
		$model               = model($modelName);

		$page   = max($this->request->get['page'] ?? 1, 1);
		$limit  = (int)($this->request->get['limit'] ?? 10);
		$start  = ($page - 1) * $limit;

		$options = [
			'start' => $start,
			'limit' => $limit,
			//'type'        => $this->type,
		] + $this->global + $this->filter;
		unset($options['user_id']);

		$results = $model->getAll($options);

		if (isset($results[$type])) {
			foreach ($results[$type] as $id => &$row) {
				if (isset($row['images'])) {
					$row['images'] = json_decode($row['images'], 1);

					foreach ($row['images'] as &$image) {
						$image = Images::image($image, $type);
					}
				} else {
					if (isset($row['image'])) {
						$row['image'] = Images::image($row['image'], $type);
					}
				}

				$params            = ['module' => "$module/$controller", $type_id => $row[$type_id]];
				$paramsList        = ['module' => "$module/$listController", $type_id => $row[$type_id]];
				$row['url']        = \Vvveb\url($params);
				$row['edit-url']   = \Vvveb\url($params);
				$row['delete-url'] = \Vvveb\url($paramsList + ['action' => 'delete', $type_id . '[]' => $row[$type_id]]);
			}
		}

		$view->status        = [0 => 'Disabled', 1 => 'Enabled'];
		$view->filter        = $this->filter;
		$view->$list         = $results[$type] ?? [];
		$view->count         = $results['count'] ?? 0;
		$view->limit         = $limit;
	}
}
