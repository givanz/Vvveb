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

use Vvveb\System\Component\ComponentBase;
use Vvveb\System\Event;
use Vvveb\System\Images;
use function Vvveb\url;

class Orders extends ComponentBase {
	public static array $defaultOptions = [
		'start'              => 0,
		'limit'              => ['url', 4],
		'user_id'            => null,
		'order_status'       => null,
		'order_status_id'    => null,
		'payment_status'     => null,
		'payment_status_id'  => null,
		'shipping_status'    => null,
		'shipping_status_id' => null,
		'email'              => null,
		'phone_number'       => null,
		'search'             => null,
		'order_by'           => ['url', 'order_id'],
		'direction'          => ['url', 'DESC'],
		'language_id'        => null,
		'site_id'            => null,
	];

	public array $options = [];

	/**
	 * Get orders list with images and order URLs.
	 *
	 * @return array<string, mixed> Orders data with order list and images.
	 */
	public function results(): array {
		$orders = new \Vvveb\Sql\OrderSQL();

		if (isset($this->options['order_by']) &&
				!in_array($this->options['order_by'], ['order_id', 'created_at', 'updated_at'])) {
			unset($this->options['order_by']);
		}

		if (isset($this->options['direction']) &&
				!in_array($this->options['direction'], ['asc', 'desc'])) {
			unset($this->options['direction']);
		}

		$results = $orders->getAll($this->options);

		if (isset($results['order'])) {
			foreach ($results['order'] as $id => &$order) {
				if (isset($order['images'])) {
					$order['images'] = json_decode($order['images'], true);

					foreach ($order['images'] as &$image) {
						$image = Images::image('order', $image);
					}
				}

				if (isset($order['image'])) {
					$order['images'][] = Images::image('order', $order['image']);
				}

				$order['url'] = url('user/orders/order', ['customer_order_id' => $order['customer_order_id']]);
			}
		}

		[$results] = Event :: trigger(__CLASS__, __FUNCTION__, $results);

		return $results;
	}
}
