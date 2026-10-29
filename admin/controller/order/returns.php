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

use Vvveb\Controller\Listing;

class Returns extends Listing {
	/** @var list<string> */
	protected ?array $additionalPermissionCheck = ['order/return-order/save'];

	protected string $type = 'return';

	protected string $controller = 'return-order';

	protected string $listController = 'returns';

	protected string $list = 'return';

	protected string $module = 'order';
}
