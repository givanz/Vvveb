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

use Vvveb\System\Cart\Order;

abstract class PaymentMethod {
	/** @var object Cart instance. */
	protected object $cart;

	/** Purchase/authorize completed synchronously. */
	public const STATUS_SUCCESS = 'success';

	/** Customer must be redirected off-site (3DS, hosted page); core performs the redirect. */
	public const STATUS_REDIRECT = 'redirect';

	/** Operation failed, see getError(). */
	public const STATUS_FAILED = 'failed';

	/** @var string|null Last failure reason, set via fail(). */
	private ?string $error = null;

	/** @var string|null Off-site redirect target, set via redirect(). */
	private ?string $redirectUrl = null;

	/**
	 * @param object $cart Cart instance.
	 */
	public function __construct(object $cart) {
		$this->cart = $cart;
	}

	/**
	 * Initialize the payment method.
	 *
	 * @return void
	 */
	abstract public function init(): void;

	/**
	 * Get payment method details and options.
	 *
	 * @param array<string, mixed> &$checkoutInfo Checkout information passed by reference.
	 * @param array<string, mixed> $options       Method-specific options.
	 *
	 * @return array<string, mixed>|false Method data or false if unavailable.
	 */
	abstract public function getMethod(array &$checkoutInfo = [], array $options = []): array|false;

	/**
	 * Set the active payment method.
	 *
	 * @return void
	 */
	abstract public function setMethod(): void;

	/**
	 * Check whether this method implements an operation.
	 *
	 * Only overridden operations count: inherited no-op stubs report false,
	 * so admin buttons and capability checks stay accurate without
	 * per-method declarations.
	 *
	 * @param string $operation One of authorize, capture, completeAuthorize,
	 *                          purchase, completePurchase, refund, void,
	 *                          createCard, webhook.
	 *
	 * @return bool True if the concrete class overrides the operation.
	 */
	public function supports(string $operation): bool {
		$method = match ($operation) {
			'authorize'                => 'authorize',
			'capture'                  => 'capture',
			'completeAuthorize'        => 'completeAuthorize',
			'purchase'                 => 'purchase',
			'completePurchase'         => 'completePurchase',
			'refund'                   => 'refund',
			'void'                     => 'void',
			'createCard'               => 'createCard',
			'webhook'                  => 'webhook',
			'createSubscription'       => 'createSubscription',
			'cancelSubscription'       => 'cancelSubscription',
			'pauseSubscription'        => 'pauseSubscription',
			'resumeSubscription'       => 'resumeSubscription',
			'swapSubscription'         => 'swapSubscription',
			'skipSubscription'         => 'skipSubscription',
			'retrySubscriptionPayment' => 'retrySubscriptionPayment',
			default                    => null,
		};

		if ($method === null) {
			return false;
		}

		try {
			$reflection = new \ReflectionMethod($this, $method);

			return $reflection->getDeclaringClass()->getName() !== self::class;
		} catch (\ReflectionException $e) {
			return false;
		}
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
	 * Get the off-site redirect target set via redirect().
	 *
	 * @return string|null Redirect URL or null when no redirect was requested.
	 */
	public function getRedirectUrl(): ?string {
		return $this->redirectUrl;
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
	 * Request an off-site redirect instead of exiting inside the method.
	 *
	 * Returned to core, which persists payment data and performs the
	 * redirect, keeping order creation and events in one place.
	 *
	 * @param string $url Redirect target.
	 *
	 * @return array{status: string, redirect: string} Redirect result.
	 */
	protected function redirect(string $url): array {
		$this->redirectUrl = $url;

		return ['status' => self::STATUS_REDIRECT, 'redirect' => $url];
	}

	/**
	 * Zero-decimal currencies (Stripe list): amounts are already in minor
	 * units, multiplying by 100 would overcharge 100x.
	 *
	 * @var array<int, string>
	 */
	public const ZERO_DECIMAL_CURRENCIES = ['BIF', 'CLP', 'DJF', 'GNF', 'JPY', 'KMF', 'KRW', 'MGA', 'PYG', 'RWF', 'UGX', 'VND', 'VUV', 'XAF', 'XOF', 'XPF'];

	/**
	 * Check whether a currency has no minor units.
	 *
	 * @param string|null $currency ISO currency code.
	 *
	 * @return bool True for zero-decimal currencies.
	 */
	public static function isZeroDecimalCurrency(?string $currency): bool {
		return $currency !== null && in_array(strtoupper($currency), self::ZERO_DECIMAL_CURRENCIES, true);
	}

	/**
	 * Convert a decimal total to integer minor units.
	 *
	 * @param mixed       $total    Decimal total.
	 * @param string|null $currency ISO currency code, 2 decimals assumed when unknown.
	 *
	 * @return int Minor units.
	 */
	public static function toMinorUnits(mixed $total, ?string $currency = null): int {
		$total = round((float)$total, 2);

		if (self::isZeroDecimalCurrency($currency)) {
			return (int)round($total);
		}

		return (int)round($total * 100);
	}

	/**
	 * Convert integer minor units back to a decimal total.
	 *
	 * @param int         $minor    Minor units.
	 * @param string|null $currency ISO currency code, 2 decimals assumed when unknown.
	 *
	 * @return float Decimal total.
	 */
	public static function fromMinorUnits(int $minor, ?string $currency = null): float {
		if (self::isZeroDecimalCurrency($currency)) {
			return (float)$minor;
		}

		return round($minor / 100, 2);
	}

	/**
	 * Detect raw card numbers (PANs) in card data.
	 *
	 * Posting PANs to the store balloons PCI scope; card details must stay
	 * in hosted fields/tokens. Matches 13-19 digit runs, ignoring spaces
	 * and dashes.
	 *
	 * @param mixed $card Card data (array, string or nested).
	 *
	 * @return bool True when a PAN-like number is present.
	 */
	protected function containsPan(mixed $card): bool {
		if (is_array($card)) {
			foreach ($card as $value) {
				if ($this->containsPan($value)) {
					return true;
				}
			}

			return false;
		}

		if (!is_scalar($card)) {
			return false;
		}

		return (bool)preg_match('/\b(?:\d[\s-]?){13,19}\b/', (string)$card);
	}

	/**
	 * Append a money-ledger row for this operation. Never throws.
	 *
	 * @param string               $kind         Operation kind (authorize, capture,
	 *                                           purchase, void, refund, renewal).
	 * @param array<string, mixed> $checkoutInfo Checkout information (order_id, total,
	 *                                           currency).
	 * @param string               $status       Result status (succeeded, failed, pending).
	 * @param string               $reference    Gateway-side reference.
	 * @param string               $note         Optional note.
	 * @param string               $gateway      Gateway name, defaults to the
	 *                                           checkout payment method.
	 *
	 * @return void
	 */
	protected function recordLedger(string $kind, array $checkoutInfo, string $status, string $reference = '', string $note = '', string $gateway = ''): void {
		if ($gateway === '') {
			$gateway = (string)($checkoutInfo['payment_method'] ?? '');
		}

		\Vvveb\System\PaymentLedger::record([
			'order_id'   => $checkoutInfo['order_id'] ?? 0,
			'kind'       => $kind,
			'gateway'    => $gateway,
			'reference'  => $reference,
			'amount'     => $checkoutInfo['amount'] ?? $checkoutInfo['total'] ?? 0,
			'tax_amount' => $checkoutInfo['tax_amount'] ?? 0,
			'currency'   => $checkoutInfo['currency'] ?? '',
			'status'     => $status,
			'note'       => $note,
		]);
	}

	/**
	 * Partial-operation amount override from checkout info.
	 *
	 * `capture()` and `refund()` honor an `amount` key (partial capture /
	 * partial refund); when absent or invalid the full `total` applies.
	 *
	 * @param array<string, mixed> $checkoutInfo Checkout info, optional `amount`.
	 *
	 * @return float|null Positive amount override or null for the full total.
	 */
	protected function partialAmount(array $checkoutInfo): ?float {
		if (!isset($checkoutInfo['amount']) || !is_numeric($checkoutInfo['amount'])) {
			return null;
		}

		$amount = (float)$checkoutInfo['amount'];

		return $amount > 0 ? $amount : null;
	}

	/**
	 * Convert a decimal total to integer minor units (cents).
	 *
	 * @param mixed $total Decimal total.
	 *
	 * @return int Minor units.
	 */
	protected function toCents(mixed $total): int {
		return self::toMinorUnits($total);
	}

	/**
	 * Build an idempotency key for the current checkout.
	 *
	 * Pass it as the gateway transaction/invoice reference so retried
	 * submits (double click, back button) don't double-charge.
	 *
	 * @param array<string, mixed> $checkoutInfo Checkout information.
	 *
	 * @return string Idempotency key.
	 */
	protected function idempotencyKey(array $checkoutInfo): string {
		if (!empty($checkoutInfo['idempotency_key'])) {
			return (string)$checkoutInfo['idempotency_key'];
		}

		return hash('sha256', implode('|', [
			$checkoutInfo['order_id'] ?? '',
			$checkoutInfo['payment_method'] ?? '',
			number_format((float)($checkoutInfo['total'] ?? 0), 2, '.', ''),
			$checkoutInfo['currency'] ?? '',
		]));
	}

	/**
	 * Read stored payment data, accepting the array or JSON string shapes
	 * used across checkout and admin contexts.
	 *
	 * @param array<string, mixed> $checkoutInfo Checkout information.
	 *
	 * @return array<string, mixed> Payment data.
	 */
	protected function storedPaymentData(array $checkoutInfo): array {
		$data = $checkoutInfo['payment_data'] ?? [];

		if (is_string($data)) {
			$data = json_decode($data, true);
		}

		return is_array($data) ? $data : [];
	}

	/**
	 * Store payment data on the checkout info and persist it to the order
	 * when an order id is available.
	 *
	 * @param array<string, mixed> $checkoutInfo Checkout information updated in place.
	 * @param array<string, mixed> $data         Payment data to store.
	 * @param int|null             $statusId     Optional payment status id to persist.
	 *
	 * @return void
	 */
	protected function storePaymentData(array &$checkoutInfo, array $data, ?int $statusId = null): void {
		$checkoutInfo['payment_data'] = json_encode($data);
		$order_id                     = $checkoutInfo['order_id'] ?? 0;

		if (!$order_id) {
			return;
		}

		$update = ['payment_data' => $checkoutInfo['payment_data']];

		if ($statusId !== null) {
			$update['payment_status_id'] = $statusId;
		}

		try {
			Order::getInstance()->edit($update, $order_id);
		} catch (\Throwable $e) {
			//persistence failure is non-fatal, data is still in $checkoutInfo
		}
	}

	/**
	 * Verify a webhook payload signature (HMAC-SHA256 helper).
	 *
	 * @param string $payload   Raw request body.
	 * @param string $signature Signature header value (hex or base64).
	 * @param string $secret    Webhook secret.
	 *
	 * @return bool True when the signature matches.
	 */
	protected function verifyHmac(string $payload, string $signature, string $secret): bool {
		if ($payload === '' || $signature === '' || $secret === '') {
			return false;
		}

		$expected = hash_hmac('sha256', $payload, $secret);
		$decoded  = base64_decode($signature, true);

		if ($decoded !== false && hash_equals($expected, bin2hex($decoded))) {
			return true;
		}

		return hash_equals($expected, strtolower(trim($signature)));
	}

	/**
	 * Authorize the payment.
	 *
	 * @param array<string, mixed> &$checkoutInfo Checkout information passed by reference.
	 *
	 * @return bool|array{status: string, redirect?: string} True on success,
	 *                                                       redirect result for off-site authorization, false on failure.
	 */
	public function authorize(array &$checkoutInfo = []): bool|array {
		return false;
	}

	/**
	 * Capture the payment.
	 *
	 * @param array<string, mixed> &$checkoutInfo Checkout information passed by reference.
	 *
	 * @return bool True on success, false on failure (see getError()).
	 */
	public function capture(array &$checkoutInfo = []): bool {
		return false;
	}

	/**
	 * Complete authorize the payment.
	 *
	 * @param array<string, mixed> &$checkoutInfo Checkout information passed by reference.
	 *
	 * @return bool True on success, false on failure (see getError()).
	 */
	public function completeAuthorize(array &$checkoutInfo = []): bool {
		return false;
	}

	/**
	 * Purchase
	 *
	 * @param array<string, mixed> &$checkoutInfo Checkout information passed by reference.
	 *
	 * @return bool|array{status: string, redirect?: string} True on success,
	 *                                                       redirect result for off-site payments, false on failure.
	 */
	public function purchase(array &$checkoutInfo = []): bool|array {
		return false;
	}

	/**
	 * Complete purchase
	 *
	 * @param array<string, mixed> &$checkoutInfo Checkout information passed by reference.
	 *
	 * @return bool True on success, false on failure (see getError()).
	 */
	public function completePurchase(array &$checkoutInfo = []): bool {
		return false;
	}

	/**
	 * Refund the payment.
	 *
	 * @param array<string, mixed> &$checkoutInfo Checkout information passed by reference.
	 *
	 * @return bool True on success, false on failure (see getError()).
	 */
	public function refund(array &$checkoutInfo = []): bool {
		return false;
	}

	/**
	 * Cancel the payment.
	 *
	 * @param array<string, mixed> &$checkoutInfo Checkout information passed by reference.
	 *
	 * @return bool True on success, false on failure (see getError()).
	 */
	public function void(array &$checkoutInfo = []): bool {
		return false;
	}

	/**
	 * Create a card reference for future payments.
	 *
	 * @param array<string, mixed> &$checkoutInfo Checkout information passed by reference.
	 *
	 * @return bool True on success, false on failure (see getError()).
	 */
	public function createCard(array &$checkoutInfo = []): bool {
		return false;
	}

	/**
	 * Handle an asynchronous gateway notification (webhook).
	 *
	 * Implementations must verify the payload signature, apply the status
	 * update idempotently and return false with getError() on failure.
	 * Core never calls this directly; plugin routes delegate to it so all
	 * drivers share one contract.
	 *
	 * @param array<string, mixed> &$checkoutInfo Checkout information passed by reference.
	 *
	 * @return bool True when the notification was handled.
	 */
	public function webhook(array &$checkoutInfo = []): bool {
		return false;
	}

	/**
	 * Resolve the gateway-side subscription reference for lifecycle operations.
	 *
	 * Controllers pass an explicit `gateway_subscription_id`; as a fallback
	 * the `payment_method` field stores `gateway:id` pairs (e.g. stripe:sub_1).
	 *
	 * @param array<string, mixed> $checkoutInfo Checkout/subscription information.
	 *
	 * @return string|null Gateway subscription id or null when not resolvable.
	 */
	protected function gatewaySubscriptionId(array $checkoutInfo): ?string {
		foreach (['gateway_subscription_id', 'subscription_reference'] as $key) {
			if (!empty($checkoutInfo[$key])) {
				return (string)$checkoutInfo[$key];
			}
		}

		$method = (string)($checkoutInfo['payment_method'] ?? '');

		if (str_contains($method, ':')) {
			$reference = substr($method, strpos($method, ':') + 1);

			if ($reference !== '') {
				return $reference;
			}
		}

		return null;
	}

	/**
	 * Create a subscription at the gateway.
	 *
	 * @param array<string, mixed> &$checkoutInfo Must carry the plan/customer context.
	 *
	 * @return bool True on success, false on failure (see getError()).
	 */
	public function createSubscription(array &$checkoutInfo = []): bool {
		return false;
	}

	/**
	 * Cancel a subscription at the gateway (immediate).
	 *
	 * @param array<string, mixed> &$checkoutInfo Must carry the gateway reference
	 *                                            (see gatewaySubscriptionId()).
	 *
	 * @return bool True on success, false on failure (see getError()).
	 */
	public function cancelSubscription(array &$checkoutInfo = []): bool {
		return false;
	}

	/**
	 * Pause a subscription at the gateway.
	 *
	 * @param array<string, mixed> &$checkoutInfo Must carry the gateway reference.
	 *
	 * @return bool True on success, false on failure (see getError()).
	 */
	public function pauseSubscription(array &$checkoutInfo = []): bool {
		return false;
	}

	/**
	 * Resume a paused subscription at the gateway.
	 *
	 * @param array<string, mixed> &$checkoutInfo Must carry the gateway reference.
	 *
	 * @return bool True on success, false on failure (see getError()).
	 */
	public function resumeSubscription(array &$checkoutInfo = []): bool {
		return false;
	}

	/**
	 * Swap the price/plan of a subscription at the gateway.
	 *
	 * Price/plan edits deliberately do NOT propagate automatically:
	 * gateway prices are immutable, so swapping live subscriptions must stay
	 * an explicit per-subscription action.
	 *
	 * @param array<string, mixed> &$checkoutInfo Must carry the gateway reference
	 *                                            plus `price_id` (or equivalent).
	 *
	 * @return bool True on success, false on failure (see getError()).
	 */
	public function swapSubscription(array &$checkoutInfo = []): bool {
		return false;
	}

	/**
	 * Skip the next billing of a subscription at the gateway (push the next
	 * payment out by one interval).
	 *
	 * @param array<string, mixed> &$checkoutInfo Must carry the gateway reference
	 *                                            plus `delay_until` (unix timestamp).
	 *
	 * @return bool True on success, false on failure (see getError()).
	 */
	public function skipSubscription(array &$checkoutInfo = []): bool {
		return false;
	}

	/**
	 * Retry a failed subscription invoice payment at the gateway.
	 *
	 * Must be a no-op when the invoice is already paid (gateway smart
	 * retries may have recovered it) and report that as success.
	 *
	 * @param array<string, mixed> &$checkoutInfo Must carry the gateway reference
	 *                                            plus `invoice_id`.
	 *
	 * @return bool True on success, false on failure (see getError()).
	 */
	public function retrySubscriptionPayment(array &$checkoutInfo = []): bool {
		return false;
	}
}
