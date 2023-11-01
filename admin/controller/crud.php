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

class Crud extends Base {
	protected $module = '';

	protected $type = '';

	function save() {
		$type        = $this->type;
		$type_id     = "{$type}_id";
		$module      = $this->module;
		$controller  = $this->controller ?? $type;

		$data_id = $this->request->get[$type_id] ?? false;
		$data    = $this->request->post[$type] ?? false;
		$model   = model($type);

		if ($data) {
			$model = model($type);

			if (! $data_id) {
				$data['created_at'] = $data['created_at'] ?? date('Y-m-d H:i:s');
			}
			$data['updated_at']    = $data['updated_at'] ?? date('Y-m-d H:i:s');
			$options               = [$type => $data] + $this->global;

			if ($data_id) {
				$options[$type_id]       = $data_id;
				$result                  = $model->edit($options);
			} else {
				$result        = $model->add($options);
			}

			if ($result && isset($result[$type])) {
				$successMessage        = humanReadable(__($type)) . __(' saved!');
				$this->view->success[] = $successMessage;

				if (! $data_id) {
					$this->session->set('success', $successMessage);
					$this->redirect(['module' => "$module/$controller", $type_id => $result[$type]]);
				}
			} else {
				$this->view->errors[] = __('Error saving!');
			}
		}

		return $this->index();
	}

	function index() {
		$type                = $this->type;
		$type_id             = "{$type}_id";
		$view                = View :: getInstance();
		$data_id             = $this->request->get[$type_id] ?? false;
		$admin_path          = \Vvveb\adminPath();

		if (isset($this->model)) {
			$modelName = $this->model;
		} else {
			$modelName = $type;
		}

		$model = model($modelName);

		$controllerPath  = $admin_path . 'index.php?module=media/media';
		$view->scanUrl   = "$controllerPath&action=scan";
		$view->uploadUrl = "$controllerPath&action=upload";

		$options = [
			$type_id         => $data_id,
		] + $this->global;
		unset($options['user_id']);

		$data = $model->get($options);

		if (isset($data['image'])) {
			$data['image_url'] = Images::image($data['image'], $type);
		}

		$this->view->status = [0 => __('Inactive'), 1 => __('Active')];
		$this->view->$type  = $data;
	}
}
