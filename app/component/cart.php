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

use function Vvveb\siteSettings;
use Vvveb\System\Cart\Cart as ShoppingCart;
use Vvveb\System\Component\ComponentBase;
use Vvveb\System\Event;

class Cart extends ComponentBase {
	public static array $defaultOptions = [
		'cart_id'     => null,
		'language_id' => null,
		'site_id'     => null,
	];

	public int $cacheExpire = 0;

	protected array $options = [];

	/**
	 * Disable caching for cart component.
	 *
	 * @return null Always returns null to disable caching.
	 */
	public function cacheKey(): ?string {
		return null;
	}

	/**
	 * Get cart contents with products, coupons, totals, and formatted grand total.
	 *
	 * @return array<string, mixed> Cart data with products, coupons, totals, and total.
	 */
	public function results(): array {
		$site = siteSettings($this->options['site_id'], $this->options['language_id']);
		$this->options += array_intersect_key(
			$site,
			array_flip(['weight_type_id', 'length_type_id', 'currency_id', 'country_id'])
		);

		$cart                       = ShoppingCart::getInstance($this->options);
		$results['products']        = $cart->getAll();
		$results['coupons']         = $cart->getCoupons();
		$results['total_items']     = $cart->getNoProducts();
		$results['totals']          = $cart->getAllTotals();
		$results['total']           = $cart->getGrandTotal();
		$results['total_formatted'] = $cart->getGrandTotalFormatted();

		[$results] = Event :: trigger(__CLASS__, __FUNCTION__, $results);

		return $results;
	}
}
