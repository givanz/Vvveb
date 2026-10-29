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
	/**
	 * Entity type identifier for regions.
	 *
	 * @var string
	 */
	protected string $type = 'region';

	/**
	 * Singular controller name for detail views.
	 *
	 * @var string
	 */
	protected string $controller = 'region';

	/**
	 * Plural controller name for listing views.
	 *
	 * @var string
	 */
	protected string $listController = 'regions';

	/**
	 * List key used to identify the data collection.
	 *
	 * @var string
	 */
	protected string $list = 'region';

	/**
	 * Module path this controller belongs to.
	 *
	 * @var string
	 */
	protected string $module = 'localization';

	/**
	 * Set the status for one or more regions.
	 *
	 * @param int $status The new status value (1 = active, 0 = inactive).
	 *
	 * @return void
	 */
	private function setStatus(int $status): void {
		$type    = $this->type;
		$type_id = $this->type_id ?: "{$type}_id";

		if (APP == 'admin') {
			$data_id = $this->request->post[$type_id];
		} else {
			$data_id = $this->request->post[$type_id] ?? $this->request->get[$type_id] ?? false;
		}

		if ($data_id) {
			if (is_numeric($data_id)) {
				$data_id = [$data_id];
			}

			if (empty($this->modelName)) {
				$this->modelName = $type;
			}

			$model               = model($this->modelName);
			$result              = $model->multiEdit([$type => ['status' => $status], $type_id => $data_id]);
			$name                = ucfirst(__($type));

			if ($result && isset($result[$type])) {
				if ($result[$type]) {
					$this->view->success[] = sprintf(__('%s(s) status changed!'), humanReadable($name));
				} else {
					$this->view->info[] = sprintf(__('No rows affected!'), humanReadable($name));
				}
			} else {
				$this->view->errors[] = sprintf(__('Error changing %s status!'), humanReadable($name));
			}
		}

		unset($this->request->get[$type_id]);

		$this->index();
	}

	/**
	 * Enable regions by setting status to active.
	 *
	 * @return void
	 */
	public function enable(): void {
		$this->setStatus(1);
	}

	/**
	 * Disable regions by setting status to inactive.
	 *
	 * @return void
	 */
	public function disable(): void {
		$this->setStatus(0);
	}
}
