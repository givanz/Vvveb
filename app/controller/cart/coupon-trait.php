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

namespace Vvveb\Controller\Cart;

use function Vvveb\__;

trait CouponTrait {
	function coupon() {
		$coupon = $this->request->request['coupon'] ?? '';

		if ($coupon) {
			if ($this->cart->addCoupon($coupon)) {
				$this->view->success['coupon'] = __('Coupon successfully applied!');
			} else {
				$this->view->errors['coupon'] = __('Invalid or expired coupon!');
			}
		}

		return $this->index();
	}

	function removeCoupon() {
		$coupon = $this->request->request['coupon'] ?? '';

		if ($coupon) {
			if ($this->cart->removeCoupon($coupon)) {
				$this->view->success['coupon'] = __('Coupon removed!');
			} else {
				$this->view->errors['coupon'] = __('Could not remove coupon!');
			}
		}

		return $this->index();
	}
}
