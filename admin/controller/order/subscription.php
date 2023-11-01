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

use function Vvveb\__;
use Vvveb\Controller\Crud;

class Subscription extends Crud {
	protected $type = 'subscription';

	protected $controller = 'subscription';

	protected $module = 'order';

	function index() {
		parent :: index();

		$this->view->period       = ['day' => __('Day'), 'week' => __('Week'), 'month' => __('Month'),  'year' => __('Year')];
		$this->view->trial_period = $this->view->period;
		$this->view->trial_status    = $this->view->status;
	}
}
