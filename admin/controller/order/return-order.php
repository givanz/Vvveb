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

namespace Vvveb\Controller\Order;

use Vvveb\Controller\Crud;
use Vvveb\Sql\Return_reasonSQL;
use Vvveb\Sql\Return_resolutionSQL;
use Vvveb\Sql\Return_statusSQL;

//Return is a reserved keyword using ReturnOrder and return-order instead
class ReturnOrder extends Crud {
	protected string $type = 'return';

	protected string $controller = 'return-order';

	protected string $module = 'order';

	/**
	 * Display the return order edit form with resolution, reason, and status data.
	 *
	 * @return void
	 */
	public function index(): void {
		parent::index();

		$resolution = new Return_resolutionSQL();
		$reason     = new Return_reasonSQL();
		$status     = new Return_statusSQL();

		$resolutions = $resolution->getAll($this->global)['return_resolution'] ?? [];
		$reasons     = $reason->getAll($this->global)['return_reason'] ?? [];
		$statuses    = $status->getAll($this->global)['return_status'] ?? [];

		$this->view->return_resolution_id = $resolutions;
		$this->view->return_reason_id     = $reasons;
		$this->view->return_status_id     = $statuses;
	}
}
