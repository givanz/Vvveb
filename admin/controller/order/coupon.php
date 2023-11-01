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

namespace Vvveb\Controller\Order;

use function Vvveb\__;
use Vvveb\Controller\Crud;
use Vvveb\Sql\CouponSQL;

class Coupon extends Crud {
	protected $type = 'coupon';

	protected $controller = 'coupon';

	protected $module = 'order';

	function save() {
		parent::save();

		$post       = &$this->request->post;
		$get        = &$this->request->get;
		$coupon_id  = $post['coupon_id'] ?? $get['coupon_id'] ?? false;
		$products   = $post['coupon_product'] ?? [];
		$taxonomy   = $post['coupon_taxonomy'] ?? [];

		$couponSQL = new CouponSQL();

		if ($products) {
			$couponSQL->setProducts(['coupon_product' => $products, 'coupon_id' => $coupon_id]);
		}

		if ($taxonomy) {
			$couponSQL->setTaxonomies(['coupon_taxonomy' => $taxonomy, 'coupon_id' => $coupon_id]);
		}

		return $this->index();
	}

	function index() {
		parent::index();
		$get        = &$this->request->get;
		$coupon_id  = $get['coupon_id'] ?? false;

		$couponSQL = new CouponSQL();

		$this->view->coupon += $couponSQL->getProducts(['coupon_id' => $coupon_id] + $this->global) ?? [];
		$this->view->coupon += $couponSQL->getTaxonomies(['coupon_id' => $coupon_id] + $this->global) ?? [];

		$this->view->type          = ['P' => __('Percentage'), 'F' => __('Fixed')];
		$this->view->free_shipping = $this->view->logged_in = ['0' => __('No'), '1' => __('Yes')];
	}
}
