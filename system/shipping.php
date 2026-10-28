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

use Vvveb\System\Cart\Cart;

class Shipping {
	/** @var array<string, array{0: string, 1: array<string, mixed>}> Registered shipping methods. */
	private array $methods = [];

	/** @var array<string, ShippingMethod> Instantiated shipping method objects. */
	private array $instances = [];

	/** @var ShippingMethod|null The currently active shipping method instance. */
	private ?ShippingMethod $instance = null;

	/**
	 * Get singleton instance of the Shipping class.
	 *
	 * @return self
	 */
	public static function getInstance(): self {
		static $inst = null;

		if ($inst === null) {
			$inst   = new self();
		}

		return $inst;
	}

	public function __construct() {}

	/**
	 * Get all available shipping methods and their data.
	 *
	 * @param array<string, mixed> &$checkoutInfo Checkout information passed by reference.
	 * @param ?object              $cart
	 *
	 * @return array<string, array<string, mixed>> Available shipping methods with data.
	 */
	public function getMethods(array &$checkoutInfo, ?object $cart = null): array {
		$data = [];
		$cart ??= Cart::getInstance();

		foreach ($this->methods as $name => $method) {
			[$class, $options]              = $method;
			$obj                            = new $class($cart);
			$this->instances[$name]         = $obj;
			$shippingData                   = $obj->getMethod($checkoutInfo, $options);
			//if shipping method returns false or no data then don't add it to the list
			if ($shippingData) {
				$data[$name] = $shippingData;
			}
		}

		return $data;
	}

	/**
	 * Register a shipping method.
	 *
	 * @param string               $method  Method identifier.
	 * @param string               $class   Fully-qualified class name.
	 * @param array<string, mixed> $options Method-specific options.
	 *
	 * @return void
	 */
	public function registerMethod(string $method, string $class, array $options = []): void {
		$this->methods[$method] = [$class, $options];
	}

	/**
	 * Set the active shipping method and initialize all methods.
	 *
	 * @param string $method Method identifier.
	 *
	 * @return void
	 */
	public function setMethod(string $method): void {
		foreach ($this->instances as $instance) {
			$instance->init();
		}

		if (isset($this->instances[$method])) {
			$this->instance = $this->instances[$method];
			$this->instance->setMethod();
		}
	}

	/**
	 * Get a specific registered shipping method instance by name.
	 * Instantiates the method on demand if it was not already created by getMethods().
	 *
	 * @param string  $method Method identifier.
	 * @param ?object $cart
	 *
	 * @return ShippingMethod|null The method instance or null if the method is not registered.
	 */
	public function getMethodInstance(string $method, ?object $cart = null): ?ShippingMethod {
		if (isset($this->instances[$method])) {
			return $this->instances[$method];
		}

		if (isset($this->methods[$method])) {
			[$class, $options]        = $this->methods[$method];
			$obj                      = new $class($cart ?? Cart::getInstance());
			$this->instances[$method] = $obj;

			return $obj;
		}

		return null;
	}

	/**
	 * Get the last failure reason from the active method.
	 *
	 * @return string|null Error message or null when the last operation succeeded.
	 */
	public function getError(): ?string {
		return $this->instance?->getError();
	}

	/**
	 * Process the shipment using the active method.
	 *
	 * @param array<string, mixed> &$checkoutInfo Checkout information passed by reference.
	 *
	 * @return bool
	 */
	public function ship(array &$checkoutInfo = []): bool {
		if ($this->instance) {
			return $this->instance->ship($checkoutInfo);
		}

		return false;
	}
}
