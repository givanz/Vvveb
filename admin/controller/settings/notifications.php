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

namespace Vvveb\Controller\Settings;

use function Vvveb\__;
use Vvveb\Controller\Base;
use Vvveb\System\Validator;

class Notifications extends Base {
	function save() {
		$this->index();
	}

	function index() {
		$validator = new Validator(['settings']);
		$settings  = $this->request->post['settings'] ?? false;
		$errors    = [];

		if ($settings &&
			($errors = $validator->validate($settings)) === true) {
			$settings              = $validator->filter($settings);
			$results               = \Vvveb\set_settings($settings);
			$this->view->success[] = __('Settings saved!');
		} else {
			$this->view->errors = $errors;
		}
	}
}
