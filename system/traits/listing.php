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

namespace Vvveb\System\Traits;

use function Vvveb\__;
use function Vvveb\humanReadable;
use function Vvveb\model;
use Vvveb\System\Images;

trait Listing {
	protected $module;

	protected $type;

	protected $list;

	protected $type_id;

	protected $data_id;

	protected $model;

	protected $modelName;

	protected $options = [];

	function init() {
		if (isset($this->request->get['type'])) {
			//$this->type = $this->request->get['type'];
		}

		return parent::init();
	}

	function delete() {
		$type    = $this->type;
		$type_id = $this->type_id ?? "{$type}_id";

		$data_id    = $this->request->post[$type_id] ?? $this->request->get[$type_id] ?? false;

		if ($data_id) {
			if (is_numeric($data_id)) {
				$data_id = [$data_id];
			}

			if (! isset($this->modelName)) {
				$this->modelName = $type;
			}

			$model               = model($this->modelName);
			$options             = [$type_id => $data_id] + $this->global;
			$result              = $model->delete($options);
			$name                = ucfirst(__($type));

			if ($result && isset($result[$type])) {
				if ($result[$type]) {
					$this->view->success[] = sprintf(__('%s(s) deleted!'), humanReadable($name));
				} else {
					$this->view->info[] = sprintf(__('No rows affected!'), humanReadable($name));
				}
			} else {
				$this->view->errors[] = sprintf(__('Error deleting %s!'), humanReadable($name));
			}
		}

		return $this->index();
	}

	function index() {
		$type           = $this->type;
		$controller  	  = $this->controller ?? $type;
		$listController = $this->listController ?? $type;
		$type_id        = $this->type_id ?? "{$type}_id";
		$data_id        = $this->data_id ?? "{$type}_id";
		$list           = $this->list ?? $type;
		$module         = $this->module;
		$this->filter   = $this->request->get['filter'] ?? [];

		if (! isset($this->modelName)) {
			$this->modelName = $type;
		}

		$model = model($this->modelName);

		$page   = max($this->request->get['page'] ?? 1, 1);
		$limit  = (int)($this->request->get['limit'] ?? 10);
		$start  = ($page - 1) * $limit;

		$options = [
			'start' => $start,
			'limit' => $limit,
			//'type'        => $this->type,
		] + $this->global + $this->filter + $this->request->get;
		unset($options['user_id']);

		if ($this->data_id && ($id = $this->request->get[$this->data_id] ?? false)) {
			$options[$this->data_id] = $id;
		}

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
			}
		}
		$results[$type] = array_values($results[$type] ?? []);

		$options['page'] = $page;

		return $results + $options;
	}
}
