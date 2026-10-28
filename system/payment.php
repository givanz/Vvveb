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

class Payment {
	/** @var array<string, array{0: string, 1: array<string, mixed>}> Registered payment methods. */
	private array $methods = [];

	/** @var array<string, PaymentMethod> Instantiated payment method objects. */
	private array $instances = [];

	/** @var PaymentMethod|null The currently active payment method instance. */
	private ?PaymentMethod $instance = null;

	/**
	 * Get singleton instance of the Payment class.
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

	/**
	 * Constructor.
	 */
	public function __construct() {}

	/**
	 * Get all available payment methods and their data.
	 *
	 * @param array<string, mixed> &$checkoutInfo Checkout information passed by reference.
	 * @param ?object              $cart
	 *
	 * @return array<string, array<string, mixed>> Available payment methods with data.
	 */
	public function getMethods(array &$checkoutInfo, ?object $cart = null): array {
		$data = [];
		$cart ??= Cart::getInstance();

		foreach ($this->methods as $name => $method) {
			[$class, $options]          =  $method;
			$obj                        = new $class($cart, $options);
			$this->instances[$name]     = $obj;
			$paymentData                = $obj->getMethod($checkoutInfo, $options);
			//if payment method returns false or no data then don't add it to the list
			if ($paymentData) {
				$data[$name] = $paymentData;
			}
		}

		return $data;
	}

	/**
	 * Register a payment method.
	 *
	 * @param string               $method  Method identifier.
	 * @param string               $class   Fully-qualified class name.
	 * @param array<string, mixed> $options Method-specific options.
	 *
	 * @return void
	 */
	public function registerMethod(string $method, string $class, array $options = []): void {
		$options['class']       = $class;
		$this->methods[$method] = [$class, $options];
	}

	/**
	 * Get a specific registered payment method instance by name.
	 * Instantiates the method on demand if it was not already created by getMethods().
	 *
	 * @param string  $method Method identifier.
	 * @param ?object $cart
	 *
	 * @return PaymentMethod|null The method instance or null if the method is not registered.
	 */
	public function getMethodInstance(string $method, ?object $cart = null): ?PaymentMethod {
		if (isset($this->instances[$method])) {
			return $this->instances[$method];
		}

		if (isset($this->methods[$method])) {
			[$class, $options]        = $this->methods[$method];
			$obj                      = new $class($cart ?? Cart::getInstance(), $options);
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
	 * Get the off-site redirect target requested by the active method.
	 *
	 * @return string|null Redirect URL or null when no redirect was requested.
	 */
	public function getRedirectUrl(): ?string {
		return $this->instance?->getRedirectUrl();
	}

	/**
	 * Check whether a registered method implements an operation.
	 *
	 * @param string $method    Method identifier.
	 * @param string $operation Operation name (see PaymentMethod::supports()).
	 *
	 * @return bool True if the method supports the operation.
	 */
	public function supports(string $method, string $operation): bool {
		return (bool)$this->getMethodInstance($method)?->supports($operation);
	}

	/**
	 * Set the active payment method and initialize all methods.
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
	 * Authorize the payment using the active method.
	 * authorize an amount on the customer's card
	 *
	 * @param array<string, mixed> &$checkoutInfo Checkout information passed by reference.
	 *
	 * @return bool|array{status: string, redirect?: string} Method result, use getError()/getRedirectUrl() for details.
	 */
	public function authorize(array &$checkoutInfo = []): bool|array {
		if ($this->instance) {
			return $this->instance->authorize($checkoutInfo);
		}

		return false;
	}

	/**
	 * Authorize the payment using the active method.
	 * handle return from off-site gateways after authorization
	 *
	 * @param array<string, mixed> &$checkoutInfo Checkout information passed by reference.
	 *
	 * @return bool
	 */
	public function completeAuthorize(array &$checkoutInfo = []): bool {
		if ($this->instance) {
			return $this->instance->completeAuthorize($checkoutInfo);
		}

		return false;
	}

	/**
	 * Capture the payment using the active method.
	 * capture an amount you have previously authorized
	 *
	 * @param array<string, mixed> &$checkoutInfo Checkout information passed by reference.
	 *
	 * @return bool
	 */
	public function capture(array &$checkoutInfo = []): bool {
		if ($this->instance) {
			return $this->instance->capture($checkoutInfo);
		}

		return false;
	}

	/**
	 * Purchase the payment using the active method.
	 * authorize and immediately capture an amount on the customer's card
	 *
	 * @param array<string, mixed> &$checkoutInfo Checkout information passed by reference.
	 *
	 * @return bool|array{status: string, redirect?: string} Method result, use getError()/getRedirectUrl() for details.
	 */
	public function purchase(array &$checkoutInfo = []): bool|array {
		if ($this->instance) {
			return $this->instance->purchase($checkoutInfo);
		}

		return false;
	}

	/**
	 * completePurchase the payment using the active method.
	 * handle return from off-site gateways after purchase
	 *
	 * @param array<string, mixed> &$checkoutInfo Checkout information passed by reference.
	 *
	 * @return bool
	 */
	public function completePurchase(array &$checkoutInfo = []): bool {
		if ($this->instance) {
			return $this->instance->completePurchase($checkoutInfo);
		}

		return false;
	}

	/**
	 * Void/Cancel the payment using the active method.
	 * generally can only be called up to 24 hours after submitting a transaction
	 *
	 * @param array<string, mixed> &$checkoutInfo Checkout information passed by reference.
	 *
	 * @return bool
	 */
	public function void(array &$checkoutInfo = []): bool {
		if ($this->instance) {
			return $this->instance->void($checkoutInfo);
		}

		return false;
	}

	/**
	 * Refund the payment using the active method.
	 * refund an already processed transaction
	 *
	 * @param array<string, mixed> &$checkoutInfo Checkout information passed by reference.
	 *
	 * @return bool
	 */
	public function refund(array &$checkoutInfo = []): bool {
		if ($this->instance) {
			return $this->instance->refund($checkoutInfo);
		}

		return false;
	}

	/**
	 * createCard
	 * get a cardReference that can be used for future payments. This might be used in a monthly billing scenario, for example.
	 *
	 * @param array<string, mixed> &$checkoutInfo Checkout information passed by reference.
	 *
	 * @return bool
	 */
	public function createCard(array &$checkoutInfo = []): bool {
		if ($this->instance && $this->instance->supports('createCard')) {
			return (bool)$this->instance->createCard($checkoutInfo);
		}

		return false;
	}

	/**
	 * Handle an asynchronous gateway notification using the active method.
	 *
	 * @param array<string, mixed> &$checkoutInfo Checkout information passed by reference.
	 *
	 * @return bool True when the notification was handled.
	 */
	public function webhook(array &$checkoutInfo = []): bool {
		if ($this->instance && $this->instance->supports('webhook')) {
			return (bool)$this->instance->webhook($checkoutInfo);
		}

		return false;
	}

	/**
	 * Run a subscription lifecycle operation on a registered method.
	 *
	 * @param string               $method    Method identifier.
	 * @param string               $operation One of createSubscription, cancelSubscription,
	 *                                        pauseSubscription, resumeSubscription, swapSubscription.
	 * @param array<string, mixed> &$info     Subscription context passed by reference.
	 *
	 * @return bool True on success, false when unsupported or failed (see getError()).
	 */
	public function subscription(string $method, string $operation, array &$info = []): bool {
		$instance = $this->getMethodInstance($method);

		if (!$instance || !$instance->supports($operation)) {
			return false;
		}

		return (bool)$instance->{$operation}($info);
	}
}
