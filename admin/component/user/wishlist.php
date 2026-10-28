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

use Vvveb\Sql\User_wishlistSQL;
use Vvveb\System\Component\ComponentBase;
use Vvveb\System\Event;
use function Vvveb\url;

class Wishlist extends ComponentBase {
	/** @var array{start: int, limit: int} Default options for wishlist listing. */
	public static array $defaultOptions = [
		'start' => 0,
		'limit' => 10,
	];

	/**
	 * Disable caching for this component.
	 *
	 * @return string|null Always returns false to disable caching.
	 */
	public function cacheKey(): ?string {
		//don't cache
		return null;
	}

	/**
	 * Get wishlist items for the current user with edit/delete URLs.
	 *
	 * @return array<string, mixed> Wishlist data with item URLs.
	 */
	public function results(): array {
		$results  = [];

		if ($this->options['user_id']) {
			$wishlist  = new User_wishlistSQL();
			$results   = $wishlist->getAll($this->options) ?? [];

			if ($results && isset($results['wishlist'])) {
				foreach ($results['wishlist'] as $id => &$wishlist) {
					$wishlist['url']        =  url('user/wishlist/edit', $wishlist);
					$wishlist['delete-url'] =  url('user/wishlist/delete', $wishlist);
				}
			}
		}

		[$results] = Event::trigger(__CLASS__, __FUNCTION__, $results);

		return $results;
	}
}
