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

use Vvveb\System\Cart\Cart as ShoppingCart;
use Vvveb\System\Component\ComponentBase;
use Vvveb\System\Event;

class Checkout extends ComponentBase {
	public static array $defaultOptions = [
		'language_id' => null,
		'site_id'     => null,
	];

	public int $cacheExpire = 0;

	protected array $options = [];

	/**
	 * Disable caching for checkout component.
	 *
	 * @return null Always returns null to disable caching.
	 */
	public function cacheKey(): ?string {
		return null;
	}

	/**
	 * Get checkout results including cart contents and totals.
	 *
	 * @return array<string, mixed> Checkout data with product, total_items, and total.
	 */
	public function results(): array {
		$cart                   = ShoppingCart::getInstance($this->options);
		$results['product']     = $cart->getAll();
		$results['total_items'] = $cart->getNoProducts();
		$results['total']       = '500$';

		[$results] = Event :: trigger(__CLASS__, __FUNCTION__, $results);

		return $results;
	}
}
