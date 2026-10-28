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
use Vvveb\System\Images;
use function Vvveb\url;

class Wishlist extends ComponentBase {
	public static array $defaultOptions = [
		'user_id'    => null,
		'start'      => 0,
		'page'       => 1,
		'limit'      => 10,
		'image_size' => 'thumb',
	];

	/**
	 * Disable caching for user wishlist component.
	 *
	 * @return null Always returns null to disable caching.
	 */
	public function cacheKey(): ?string {
		return null;
	}

	/**
	 * Get user wishlist items with product images and URLs.
	 *
	 * @return array<string, mixed> Wishlist data with user_wishlist items.
	 */
	public function results(): array {
		$results  = [];

		if ($page = $this->options['page']) {
			$this->options['start'] = ($page - 1) * $this->options['limit'];
		}

		if ($this->options['user_id']) {
			$wishlist  = new User_wishlistSQL();
			$results   = $wishlist->getAll($this->options);

			if ($results && isset($results['user_wishlist'])) {
				foreach ($results['user_wishlist'] as $id => &$product) {
					if (isset($product['image']) && $product['image']) {
						$product['image'] = Images::image($product['image'], 'product', $this->options['image_size']);
					}

					$url                   = ['slug' => $product['slug'], 'product_id' => $product['product_id']];
					$product['url']        = url('product/product/index', $url);
					$product['delete-url'] =  url('user/wishlist/delete', $product);
				}
			}
		}

		[$results] = Event :: trigger(__CLASS__, __FUNCTION__, $results);

		return $results;
	}

	/**
	 * Called on each request (no-op for wishlist).
	 *
	 * @param array<string, mixed> $results Component results.
	 * @param int                  $index   Request index.
	 *
	 * @return void
	 */
	public function request(array $results, int $index = 0): void {}
}
