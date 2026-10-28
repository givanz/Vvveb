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

use function Vvveb\humanReadable;
use Vvveb\System\Cart\Currency;
use Vvveb\System\Component\ComponentBase;
use Vvveb\System\Core\Request;
use Vvveb\System\Event;
use Vvveb\System\Images;
use Vvveb\System\Sites;

class Order extends ComponentBase {
	public static array $defaultOptions = [
		'order_id'          => null,
		'customer_order_id' => null,
		'user_id'           => null,
		'language_id'       => null,
		'email'             => null,
		'image_size'        => 'thumb',
	];

	public array $options = [];

	public int $cacheExpire = 0;

	/**
	 * Get single order details with products, images, totals, and shipping/payment data.
	 *
	 * @return array<string, mixed> Order data with products, totals, and formatted prices.
	 */
	public function results(): array {
		$results = [];

		$request = Request::getInstance();

		if (isset($this->options['order_id']) || isset($this->options['customer_order_id'])) {
			$orders = new \Vvveb\Sql\OrderSQL();

			$results = $orders->get($this->options);
			$site    = Sites :: getSiteData(SITE_ID);
			$scheme  = $_SERVER['REQUEST_SCHEME'] ?? 'http';
			$url     = ['host' => SITE_URL, 'scheme' => $scheme];
			$host    = $scheme . '://' . SITE_URL;

			if ($results && $results['order']) {
				$currency = Currency::getInstance();

				if (isset($results['products'])) {
					foreach ($results['products'] as $id => &$product) {
						$product['url'] = htmlspecialchars(\Vvveb\url('product/product/index', $product + $url));

						if (isset($product['images'])) {
							$product['images'] = json_decode($product['images'], true);

							foreach ($product['images'] as &$image) {
								$image['image'] = Images::image($image['image'], 'product', $this->options['image_size']);
								//don't add host for remote images
								if (!str_contains($image['image'], ':')) {
									$image['image'] = $host . $image['image'];
								}
							}
						}

						if (isset($product['image']) && $product['image']) {
							$product['image'] = Images::image($product['image'], 'product', $this->options['image_size']);
							//don't add host for remote images
							if (!str_contains($product['image'], ':')) {
								$product['image'] = $host . $product['image'];
							}
						}

						if (isset($product['option_value'])) {
							$product['option_value'] = json_decode($product['option_value'], true);

							foreach ($product['option_value'] as &$option) {
								if (isset($option['price'])) {
									$option['price_formatted'] = $currency->format($option['price']);
								}
							}
						}

						$product['tax_formatted'] = $currency->format($product['tax']);
					}
				}

				foreach ($results['total'] as $id => &$total) {
					$total['value_formatted'] = $currency->format($total['value']);
				}

				$order                    = &$results['order'];
				$order['total_formatted'] = $currency->format($order['total']);
				$shippingData             = isset($order['shipping_data']) ? json_decode($order['shipping_data'], true) : [];
				$paymentData              = isset($order['payment_data']) ? json_decode($order['payment_data'], true) : [];

				if (!is_array($shippingData)) {
					$shippingData = [];
				}

				if (!is_array($paymentData)) {
					$paymentData = [];
				}

				$order['shipping_data'] = $shippingData;
				$order['payment_data']  = $paymentData;

				//human readable method titles, falling back to the stored method
				//key (minus the -index suffix) when the gateway did not record one
				$shippingMethod                = preg_replace('/-\d+$/', '', (string)($order['shipping_method'] ?? ''));
				$paymentMethod                 = preg_replace('/-\d+$/', '', (string)($order['payment_method'] ?? ''));
				$order['shipping_title']       = $shippingData['title'] ?? $shippingData['gateway'] ?? ($shippingMethod ? humanReadable($shippingMethod) : '');
				$order['payment_title']        = $paymentData['title'] ?? $paymentData['gateway'] ?? ($paymentMethod ? humanReadable($paymentMethod) : '');
				$order['shipping_description'] = $shippingData['description'] ?? $shippingData['text'] ?? '';
				$order['payment_description']  = $paymentData['description'] ?? $paymentData['text'] ?? '';
				//legacy alias, the customer note column is `notes`
				$order['comment'] = $order['notes'] ?? '';
			}

			[$results] = Event :: trigger(__CLASS__, __FUNCTION__, $results);
		}

		return $results;
	}
}
