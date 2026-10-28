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

namespace Vvveb\System;

abstract class ShippingMethod {
	/** @var object Cart instance. */
	protected object $cart;

	/** @var string|null Last failure reason, set via fail(). */
	private ?string $error = null;

	/**
	 * @param object $cart Cart instance.
	 */
	public function __construct(object $cart) {
		$this->cart = $cart;
	}

	/**
	 * Check whether this method implements an operation.
	 *
	 * @param string $operation One of ship (tracking/label booking stay on carriers).
	 *
	 * @return bool True if the concrete class overrides the operation.
	 */
	public function supports(string $operation): bool {
		return method_exists($this, $operation);
	}

	/**
	 * Get the last failure reason.
	 *
	 * @return string|null Error message or null when the last operation succeeded.
	 */
	public function getError(): ?string {
		return $this->error;
	}

	/**
	 * Record a failure reason and return false for convenience.
	 *
	 * @param string $message Human readable failure reason.
	 *
	 * @return bool Always false.
	 */
	protected function fail(string $message): bool {
		$this->error = $message;

		return false;
	}

	/**
	 * Record a shipment row for an order, skipping duplicates.
	 *
	 * @param int|string $orderId  Order id.
	 * @param string     $method   Method identifier shown in admin.
	 * @param string     $tracking Tracking number, if known.
	 *
	 * @return bool True on success (or when the row already exists).
	 */
	protected function recordShipment(int|string $orderId, string $method, string $tracking = ''): bool {
		try {
			$model = \Vvveb\model('order_shipment');
			$rows  = $model->getByOrder(['order_id' => (int)$orderId])['order_shipment'] ?? [];

			foreach ($rows as $row) {
				if (($row['shipping_method'] ?? '') === $method && ($row['tracking_number'] ?? '') === $tracking) {
					return true;
				}
			}

			$model->add(['shipment' => [
				'order_id'        => (int)$orderId,
				'shipping_method' => $method,
				'tracking_number' => $tracking,
			]]);

			return true;
		} catch (\Throwable $e) {
			return $this->fail('Shipment could not be recorded: ' . $e->getMessage());
		}
	}

	/**
	 * Initialize the shipping method.
	 *
	 * @return void
	 */
	abstract public function init(): void;

	/**
	 * Get shipping method details and options.
	 *
	 * @param array<string, mixed> &$checkoutInfo Checkout information passed by reference.
	 * @param array<string, mixed> $options       Method-specific options.
	 *
	 * @return array<string, mixed>|false Method data or false if unavailable.
	 */
	abstract public function getMethod(array &$checkoutInfo = [], array $options = []): array|false;

	/**
	 * Set the active shipping method.
	 *
	 * @return void
	 */
	abstract public function setMethod(): void;

	/**
	 * Process the shipment.
	 *
	 * @param array<string, mixed> &$checkoutInfo Checkout information passed by reference.
	 *
	 * @return bool
	 */
	abstract public function ship(array &$checkoutInfo = []): bool;
}
