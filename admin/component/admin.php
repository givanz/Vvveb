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

use Vvveb\System\Component\ComponentBase;
use Vvveb\System\Event;
use Vvveb\System\Images;
use Vvveb\System\User\Admin as AdminUser;

class Admin extends ComponentBase {
	/** @var array{limit: int, page: int} Default options for admin user listing. */
	public static array $defaultOptions = [
		'limit' => 1000,
		'page'  => 1,
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
		// disable caching
		return null;
	}

	/**
	 * Get current admin user data with resolved avatar and cover images.
	 *
	 * @return array<string, mixed> Current admin user data with resolved images.
	 */
	public function results(): array {
		$results       = AdminUser::current();

		foreach (['avatar', 'cover'] as $image) {
			if (isset($results[$image])) {
				$results[$image] = Images::image($results[$image], 'admin');
			}
		}

		[$results] = Event::trigger(__CLASS__, __FUNCTION__, $results);

		return $results;
	}
}
