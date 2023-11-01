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

use function Vvveb\prefixArrayKeys;
use Vvveb\System\Cart\Currency;
use Vvveb\System\Component\ComponentBase;
use Vvveb\System\Core\Request;
use Vvveb\System\Event;
use Vvveb\System\Images;

class Order extends ComponentBase {
	public static $defaultOptions = [
		'user_id'          => null,
		'order_id'         => null,
		'limit'            => ['url', 4],
	];

	public $options = [];

	function results() {
		$results = [];

		$request = Request::getInstance();

		if ($this->options['order_id']) {
			$orders = new \Vvveb\Sql\OrderSQL();

			$results = $orders->get($this->options);

			if ($results) {
				$currency = Currency::getInstance();

				if (isset($results['products'])) {
					foreach ($results['products'] as $id => &$product) {
						$product['url'] = htmlentities(\Vvveb\url('product/product/index', $product));

						if (isset($product['images'])) {
							$product['images'] = json_decode($product['images'], true);

							foreach ($product['images'] as &$image) {
								$image['image'] = Images::image($image['image'], 'product');
							}
						}

						if (isset($product['image']) && $product['image']) {
							$product['image'] =Images::image($product['image'], 'product');
							//$product['images'][] = ['image' => Images::image($product['image'], 'product')];
						}

						$product['tax_formatted'] = $currency->format($product['tax'], '$');
					}
				}

				foreach ($results['total'] as $id => &$total) {
					$total['value_formatted'] = $currency->format($total['value'], '$');
				}

				$order                    = &$results['order'];
				$order['total_formatted'] = $currency->format($order['total'], '$');
				$order['shipping_data']   = json_decode($order['shipping_data'], true);
				$order['payment_data']    = json_decode($order['payment_data'], true);

				$order += prefixArrayKeys('shipping_', $order['shipping_data']) ?? [];
				$order += prefixArrayKeys('payment_', $order['payment_data']) ?? [];
			}

			//\Vvveb\dd($results);
			list($results) = Event :: trigger(__CLASS__,__FUNCTION__, $results);
		}

		return $results;
	}
}
