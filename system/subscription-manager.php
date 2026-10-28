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

class SubscriptionManager {
	/**
	 * Extract the gateway-side subscription reference from a local row.
	 *
	 * @param array<string, mixed> $subscription Local subscription row.
	 *
	 * @return array{0: string, 1: string}|null [gateway, reference] or null when not provisioned.
	 */
	public static function gatewayReference(array $subscription): ?array {
		$reference = (string)($subscription['payment_method'] ?? '');

		if (!str_contains($reference, ':')) {
			return null;
		}

		[$gateway, $id] = explode(':', $reference, 2);

		if ($gateway === '' || $id === '') {
			return null;
		}

		return [$gateway, $id];
	}

	/**
	 * Add one billing interval to a date (`day|week|month|year` x cycle).
	 *
	 * @param string $date   Base datetime (`Y-m-d H:i:s`).
	 * @param string $period Interval unit.
	 * @param int    $cycle  Interval multiplier.
	 *
	 * @return string Resulting datetime (`Y-m-d H:i:s`), input on failure.
	 */
	public static function addInterval(string $date, string $period, int $cycle): string {
		$cycle = max(1, $cycle);
		$spec  = match ($period) {
			'day'   => "P{$cycle}D",
			'week'  => "P{$cycle}W",
			'month' => "P{$cycle}M",
			'year'  => "P{$cycle}Y",
			default => '',
		};

		if ($spec === '') {
			return $date;
		}

		try {
			return (new \DateTimeImmutable($date))->add(new \DateInterval($spec))->format('Y-m-d H:i:s');
		} catch (\Throwable $e) {
			return $date;
		}
	}

	/**
	 * Fetch the offer (price + interval) of a plan for a product.
	 *
	 * @param int $productId  Local product id.
	 * @param int $planId     Local subscription plan id.
	 * @param int $languageId Language id for plan names.
	 *
	 * @return array<string, mixed> Offer row or empty array<array-key, mixed>.
	 */
	public static function planOffer(int $productId, int $planId, int $languageId = 1): array {
		try {
			$rows = \Vvveb\model('product_subscription')->getAll([
				'product_id'           => $productId,
				'subscription_plan_id' => $planId,
				'language_id'          => $languageId,
			]);
		} catch (\Throwable $e) {
			return [];
		}

		foreach ((array)$rows as $list) {
			if (is_array($list) && $list && is_array(reset($list))) {
				return reset($list);
			}
		}

		return [];
	}

	/** Failed payment retry policy: attempt => days after the previous attempt. */
	public const DUNNING_RETRY_DAYS = [1 => 0, 2 => 1, 3 => 3, 4 => 7];

	public const DUNNING_MAX_ATTEMPTS = 4;

	/**
	 * Canonical dunning note for a failed payment attempt.
	 *
	 * @param int    $attempt Attempt number (1-based).
	 * @param string $invoice Gateway invoice reference.
	 *
	 * @return string Note text.
	 */
	public static function dunningNote(int $attempt, string $invoice): string {
		return sprintf('Dunning attempt %d/%d for invoice %s', $attempt, self::DUNNING_MAX_ATTEMPTS, $invoice);
	}

	/**
	 * Parse a dunning note back into attempt data.
	 *
	 * @param string $note Log note text.
	 *
	 * @return array{attempt: int, invoice: string}|null
	 */
	public static function parseDunningNote(string $note): ?array {
		if (preg_match('/Dunning attempt (\d+)\/\d+ for invoice (\S+)/', $note, $m)) {
			return ['attempt' => (int)$m[1], 'invoice' => $m[2]];
		}

		return null;
	}

	/**
	 * Record a dunning attempt for a subscription invoice.
	 *
	 * Counts prior attempts for the same invoice from the log so retries
	 * across webhooks and cron runs share one ladder.
	 *
	 * @param int    $subscriptionId Local subscription id.
	 * @param string $invoice        Gateway invoice reference.
	 * @param int    $statusId       Status id for the log row.
	 *
	 * @return int Attempt number just recorded (1-based).
	 */
	public static function recordDunningAttempt(int $subscriptionId, string $invoice, int $statusId = 3): int {
		$attempt = 1;
		$suffix  = '';

		try {
			$logs = \Vvveb\model('subscription_log')->getAll(['subscription_id' => $subscriptionId]);
			$rows = [];

			foreach ((array)$logs as $list) {
				if (is_array($list) && $list && is_array(reset($list))) {
					$rows = array_merge($rows, array_values($list));
				}
			}

			foreach ($rows as $row) {
				$parsed = self::parseDunningNote((string)($row['note'] ?? ''));

				if ($parsed && $parsed['invoice'] === $invoice) {
					$attempt = max($attempt, $parsed['attempt'] + 1);
				}
			}

			\Vvveb\model('subscription_log')->add(['subscription_log' => [
				'subscription_id'        => $subscriptionId,
				'subscription_status_id' => $statusId,
				'notify'                 => 0,
				'note'                   => mb_substr(self::dunningNote($attempt, $invoice) . $suffix, 0, 500),
				'created_at'             => date('Y-m-d H:i:s'),
			]]);
		} catch (\Throwable $e) {
		}

		return $attempt;
	}

	/**
	 * Latest dunning attempt across log rows.
	 *
	 * @param array<int, array<string, mixed>> $logs Subscription log rows.
	 *
	 * @return array{attempt: int, invoice: string, created_at: string}|null
	 */
	public static function latestDunning(array $logs): ?array {
		$latest = null;

		foreach ($logs as $row) {
			$parsed = self::parseDunningNote((string)($row['note'] ?? ''));

			if (!$parsed) {
				continue;
			}

			$created = (string)($row['created_at'] ?? '');

			if ($latest === null || $created >= $latest['created_at']) {
				$latest = $parsed + ['created_at' => $created];
			}
		}

		return $latest;
	}

	/**
	 * Run a gateway lifecycle operation for a local subscription row.
	 *
	 * Returns null when there is nothing to do at a gateway (not
	 * provisioned, method unavailable, operation unsupported) so callers
	 * keep the legacy local-only behavior. Returns an error message when
	 * the gateway call failed: callers must NOT change local status then.
	 *
	 * @param string                    $operation    One of cancelSubscription, pauseSubscription,
	 *                                                resumeSubscription, swapSubscription, skipSubscription.
	 * @param array<string, mixed>      $subscription Local subscription row.
	 * @param array<string, mixed>|null $order        Original order row (authoritative payment method).
	 * @param array<string, mixed>      $extra        Extra context (e.g. price_id for swaps).
	 *
	 * @return string|null Null on success/local-only, error message on gateway failure.
	 */
	public static function gatewayOperation(string $operation, array $subscription, ?array $order = null, array $extra = []): ?string {
		$reference = self::gatewayReference($subscription);

		if (!$reference) {
			return null;
		}

		[$gateway, $gatewayId] = $reference;
		$method                = $order['payment_method'] ?? $gateway;

		if (!$method) {
			return null;
		}

		try {
			$payment  = Payment::getInstance();
			$instance = $payment->getMethodInstance((string)$method);
		} catch (\Throwable $e) {
			return null;
		}

		if (!$instance || !$instance->supports($operation)) {
			return null;
		}

		$info = [
			'gateway_subscription_id' => $gatewayId,
			'subscription_id'         => $subscription['subscription_id'] ?? 0,
			'order_id'                => $subscription['order_id'] ?? ($order['order_id'] ?? 0),
			'user_id'                 => $subscription['user_id'] ?? ($order['user_id'] ?? 0),
		] + $extra;

		try {
			if ($instance->{$operation}($info)) {
				return null;
			}
		} catch (\Throwable $e) {
			return $e->getMessage();
		}

		return $instance->getError() ?: \Vvveb\__('Gateway operation failed!');
	}
}
