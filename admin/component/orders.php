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

use Vvveb\System\Cart\Currency;
use Vvveb\System\Component\ComponentBase;
use Vvveb\System\Event;
use Vvveb\System\Images;

class Orders extends ComponentBase {
	/** @var array<string, mixed> Default options for order listing. */
	public static array $defaultOptions = [
		'start'           => 0,
		'limit'           => 10,
		'language_id'     => null,
		'site_id'         => null,
		'user_id'         => null,
		'email'           => null,
		'phone_number'    => null,
		'search'          => null,
		'id_manufacturer' => null,
		'order_status'    => null,
		'order_status_id' => null,
		'order_by'        => null,
		'direction'       => ['url', 'asc', 'desc'],
	];

	/** @var array<string, mixed> Resolved options for this component. */
	public array $options = [];

	/**
	 * Get orders with validated sort parameters, resolved images, and formatted totals.
	 *
	 * @return array<string, mixed> Orders with formatted prices and resolved images.
	 */
	public function results(): array {
		if (isset($this->options['order_by']) &&
				!in_array($this->options['order_by'], ['order_id', 'customer_order_id', 'order_status_id', 'created_at'])) {
			unset($this->options['order_by']);
		}

		if (isset($this->options['direction']) &&
				!in_array($this->options['direction'], ['asc', 'desc'])) {
			unset($this->options['direction']);
		}

		$orders = new \Vvveb\Sql\OrderSQL();

		$results = $orders->getAll($this->options) ?? [];

		if (!empty($results['order'])) {
			$currency = Currency::getInstance();

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

				$order['total_formatted'] = $currency->format($order['total']);
			}
		}

		[$results] = Event::trigger(__CLASS__, __FUNCTION__, $results);

		return $results;
	}
}
