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

use Vvveb\Controller\Listing;

class SubscriptionStatuses extends Listing {
	/** @var string The entity type identifier */
	protected string $type = 'subscription_status';

	/** @var string The single-item controller route slug */
	protected string $controller = 'subscription-status';

	/** @var string The list controller route slug */
	protected string $listController = 'subscription-statuses';

	/** @var string The list property name on the view */
	protected string $list = 'subscription_status';

	/** @var string The module namespace */
	protected string $module = 'settings';
}
