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

trait Crud {
	protected $module = '';

	protected $type = '';

	protected $controller = '';

	protected $redirect = true;

	protected $data_id = false;

	protected $type_id;

	protected $options = [];

	protected $data = [];

	function delete() {
		$type    = $this->type;
		$type_id = $this->type_id ?? "{$type}_id";

		$data_id    = $this->request->post[$type_id] ?? $this->request->get[$type_id] ?? false;

		if ($data_id) {
			if (is_numeric($data_id)) {
				$data_id = [$data_id];
			}

			if (! isset($this->modelName)) {
				$modelName = $type;
			}

			$model               = model($modelName);
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

	function save() {
		$type        = $this->type;
		$type_id     = "{$type}_id";
		$module      = $this->module;
		$controller  = $this->controller ?? $type;

		$this->data_id = $this->request->get[$type_id] ?? false;
		$this->data    = $this->request->post[$type] ?? false;

		if ($this->data) {
			if (! isset($this->modelName)) {
				$this->modelName = $type;
			}

			$this->model = model($this->modelName);

			if (! $this->data_id) {
				$this->data['created_at'] = $this->data['created_at'] ?? date('Y-m-d H:i:s');
			}
			$this->data['updated_at'] = $this->data['updated_at'] ?? date('Y-m-d H:i:s');
			$options                  = [$type => $this->data] + $this->global;

			if ($this->data_id) {
				$options[$type_id] = $this->data_id;
				$this->$type_id    = $this->data_id;
				$result            = $this->model->edit($options);
			} else {
				$result         = $this->model->add($options);
				$this->$type_id = $result[$type] ?? false;
			}

			if ($result && isset($result[$type])) {
				$successMessage        = humanReadable(__($type)) . __(' saved!');
				$this->view->success[] = $successMessage;

				$this->session->set('success', $successMessage);

				if (! $this->data_id && $this->redirect) {
					$this->redirect(['module' => "$module/$controller", $type_id => $result[$type]]);
				}
			} else {
				$this->view->errors[] = __('Error saving!');
			}
		}

		if ($this->redirect) {
			return $this->index();
		}
	}

	protected function get() {
		$type             = $this->type;
		$type_id          = $this->type_id ?? "{$type}_id";
		$this->data_id    = $this->request->get[$type_id] ?? false;
		$this->data       = [];

		if ($this->data_id) {
			if (! isset($this->modelName)) {
				$this->modelName = $type;
			}

			$this->model = model($this->modelName);

			$this->options += [
				$type_id         => $this->data_id,
			] + $this->global;
			unset($this->options['user_id']);

			$result = $this->model->get($this->options);

			if ($result) {
				$this->data = $result;

				if (isset($this->data['image'])) {
					$this->data['image_url'] = Images::image($this->data['image'], $type);
				}
			}
		}

		return $this->data;
	}
}
