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

namespace Vvveb\Component;

use function Vvveb\session as sess;
use Vvveb\System\Component\ComponentBase;
use Vvveb\System\Event;
use Vvveb\System\Sites;

class States extends ComponentBase {
	/** @var array<string, mixed> Default options (empty for states). */
	public static array $defaultOptions = [
	];

	/** @var array<string, mixed> Resolved options for this component. */
	protected array $options = [];

	/** @var int Cache expiry time in seconds (0 = disabled). */
	public int $cacheExpire = 0;

	/**
	 * Disable caching for this component.
	 *
	 * @return string|null Always returns false to disable caching.
	 */
	public function cacheKey(): ?string {
		//disable caching
		return null;
	}

	/**
	 * Get application states (e.g. live, maintenance) with active state info.
	 *
	 * @return array{active_name: string, active_icon: string, states: array<string, mixed>, active: string}
	 */
	public function results(): array {
		$states                 = Sites::getStates();
		$active                 = sess('state') ?? 'live';
		$results['active_name'] = $states[$active]['name'];
		$results['active_icon'] = $states[$active]['icon'];

		$results['states'] = $states;
		$results['active'] = $active;

		[$results] = Event::trigger(__CLASS__, __FUNCTION__, $results);

		return $results;
	}
}
