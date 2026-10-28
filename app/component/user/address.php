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

namespace Vvveb\Component\User;

use Vvveb\Sql\User_addressSQL;
use Vvveb\System\Component\ComponentBase;
use Vvveb\System\Event;
use function Vvveb\url;

class Address extends ComponentBase {
	public static array $defaultOptions = [
		'user_id' => null,
		'start'   => 0,
		'limit'   => 10,
	];

	/**
	 * Disable caching for user address component.
	 *
	 * @return null Always returns null to disable caching.
	 */
	public function cacheKey(): ?string {
		return null;
	}

	/**
	 * Get user addresses with edit and delete URLs.
	 *
	 * @return array<string, mixed> Address data with user_address items.
	 */
	public function results(): array {
		$results  = [];

		if (isset($this->options['user_id'])) {
			$user_address  = new User_addressSQL();
			$results       = $user_address->getAll($this->options);

			if ($results && isset($results['user_address'])) {
				foreach ($results['user_address'] as $id => &$user_address) {
					$user_address['url']        =  url('user/address/edit', $user_address);
					$user_address['delete-url'] =  url('user/address/delete', $user_address);
				}
			}
		}

		[$results] = Event :: trigger(__CLASS__, __FUNCTION__, $results);

		return $results;
	}

	/**
	 * Called on each request (no-op for user address).
	 *
	 * @param array<string, mixed> $results Component results.
	 * @param int                  $index   Request index.
	 *
	 * @return void
	 */
	public function request(array $results, int $index = 0): void {}
}
