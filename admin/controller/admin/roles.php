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

use Vvveb\Controller\Listing;

class Roles extends Listing {
	/**
	 * Role type identifier.
	 *
	 * @var string
	 */
	protected string $type = 'role';

	/**
	 * Singular controller name for detail views.
	 *
	 * @var string
	 */
	protected string $controller = 'role';

	/**
	 * Plural controller name for listing views.
	 *
	 * @var string
	 */
	protected string $listController = 'roles';

	/**
	 * List key used to identify the data collection.
	 *
	 * @var string
	 */
	protected string $list = 'role';

	/**
	 * Module path this controller belongs to.
	 *
	 * @var string
	 */
	protected string $module = 'admin';
}
