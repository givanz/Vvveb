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

namespace Vvveb\Controller\Localization;

use function Vvveb\__;
use Vvveb\Controller\Listing;
use function Vvveb\humanReadable;
use function Vvveb\model;

class Regions extends Listing {
	protected $type = 'region';

	protected $controller = 'region';

	protected $listController = 'regions';

	protected $list = 'region';

	protected $module = 'localization';

	private function setStatus($status) {
		$type    = $this->type;
		$type_id = $this->type_id ?? "{$type}_id";

		if (APP == 'admin') {
			$data_id = $this->request->post[$type_id];
		} else {
			$data_id = $this->request->post[$type_id] ?? $this->request->get[$type_id] ?? false;
		}

		if ($data_id) {
			if (is_numeric($data_id)) {
				$data_id = [$data_id];
			}

			if (! isset($this->modelName)) {
				$this->modelName = $type;
			}

			$model               = model($this->modelName);
			//$options             = [$type_id => $data_id] + $this->global;
			$result              = $model->multiEdit([$type => ['status' => $status], $type_id => $data_id]);
			$name                = ucfirst(__($type));

			if ($result && isset($result[$type])) {
				if ($result[$type]) {
					$this->view->success[] = sprintf(__('%s(s) status changed!'), humanReadable($name));
				} else {
					$this->view->info[] = sprintf(__('No rows affected!'), humanReadable($name));
				}
			} else {
				$this->view->errors[] = sprintf(__('Error %s!'), humanReadable($name));
			}
		}

		unset($this->request->get[$type_id]);

		return $this->index();
	}
	function enable() {
		$this->setStatus(1);
	}

	function disable() {
		$this->setStatus(0);
	}
}
