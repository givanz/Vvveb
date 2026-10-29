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

namespace Vvveb\Controller\Admin;

use Vvveb\Controller\User\Users as UsersBase;
use Vvveb\System\User\Admin;

class Users extends UsersBase {
	/**
	 * User type identifier for admin users.
	 *
	 * @var string
	 */
	protected string $type = 'admin';

	/**
	 * Module path for this controller.
	 *
	 * @var string
	 */
	protected string $module = 'admin/users';

	/**
	 * Display the list of admin users, filtering by capability.
	 *
	 * @param array<string, mixed> $options Additional query options.
	 *
	 * @return void
	 */
	public function index(array $options = []): void {
		if (Admin::hasCapability('view_other_admin')) {
			unset($options['admin_id']);
		} else {
			$options['admin_id'][] = $this->global['admin_id'];
		}

		parent::index($options);
	}
}
