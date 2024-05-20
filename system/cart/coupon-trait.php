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
	protected $coupons = [];

	public function addCoupon($code) {
		if ($code) {
			$coupon  = new CouponSQL();
			$options = $this->options + ['code' => $code, 'status' => 1];
			$result  = $coupon->get($options);

			if ($result && $result['code'] == $code) {
				$this->coupons[$code] = $result;

				return true;
			}
		}

		return false;
	}

	public function removeCoupon($code) {
		$coupon = $this->coupons[$code];
		$this->removeTotal('coupon.' . $coupon['coupon_id']);
		unset($this->coupons[$code]);

		return true;
	}

	public function getCoupons() {
		return $this->coupons;
	}

	function addCouponTotal() {
		$coupons = $this->getCoupons();

		foreach ($coupons as $coupon) {
			if ($coupon['type'] == 'P') {
				$discount = (($coupon['discount'] * $this->getSubTotal()) / 100);
			} else {
				$discount = $coupon['discount'];
			}

			$this->addTotal('coupon.' . $coupon['coupon_id'], $coupon['name'], $discount);
		}
	}
}
