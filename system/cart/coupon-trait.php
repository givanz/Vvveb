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

namespace Vvveb\System\Cart;

use Vvveb\Sql\CouponSQL;

trait CouponTrait {
	/** @var array<string, array<string, mixed>> */
	protected array $coupons = [];

	/**
	 * Validate and add a coupon code to the cart.
	 *
	 * @param string $code
	 */
	public function addCoupon(string $code): bool {
		if ($code) {
			$coupon  = new CouponSQL();
			$options = ['code' => $code, 'status' => 1] + $this->options;
			$result  = $coupon->get($options);

			if ($result && $result['code'] === $code) {
				$this->coupons[$code] = $result;

				return true;
			}
		}

		return false;
	}

	/**
	 * Remove a coupon code from the cart.
	 *
	 * @param string $code
	 */
	public function removeCoupon(string $code): bool {
		if (!isset($this->coupons[$code])) {
			return false;
		}

		$coupon = $this->coupons[$code];
		$this->removeTotal('coupon', 'coupon.' . $coupon['coupon_id']);
		unset($this->coupons[$code]);

		return true;
	}

	/**
	 * Get all applied coupons.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	public function getCoupons(): array {
		return $this->coupons;
	}

	/**
	 * Calculate and add coupon discount totals.
	 */
	public function addCouponTotal(): void {
		$coupons = $this->getCoupons();

		$total = $this->getSubTotal();
		//include taxes in discount calculation
		$taxes = $this->getTotals('tax');
		foreach ($taxes as $tax) {
			$total += $tax['value'];
		}

		foreach ($coupons as $code => $coupon) {
			$discount = $coupon['discount'] ?? 0;

			if (($coupon['type'] ?? '') === 'P') {
				$discount = (($discount * $total) / 100);
			}
			$this->addTotal('coupon', 'coupon.' . ($coupon['coupon_id'] ?? $code), $coupon['name'] ?? (string)$code, -$discount);
		}
	}
}
