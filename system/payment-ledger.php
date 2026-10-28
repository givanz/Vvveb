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

/**
 * Money-event ledger: one chronological row per funds movement.
 *
 */
class PaymentLedger {
	/**
	 * Append a ledger row. Never throws.
	 *
	 * @param array<string, mixed> $data order_id, kind (authorize, capture,
	 *                                   purchase, void, refund, renewal),
	 *                                   gateway, reference, amount, currency,
	 *                                   status (succeeded, failed, pending),
	 *                                   note, tax_amount (tax portion of the
	 *                                   movement, 0 when unknown).
	 *
	 * @return int|null Insert id or null when skipped/failed.
	 */
	public static function record(array $data): ?int {
		try {
			$orderId = (int)($data['order_id'] ?? 0);

			if (!$orderId) {
				return null;
			}

			$result = \Vvveb\model('order_transaction')->add(['transaction' => [
				'order_id'   => $orderId,
				'kind'       => substr((string)($data['kind'] ?? ''), 0, 32),
				'gateway'    => substr((string)($data['gateway'] ?? ''), 0, 64),
				'reference'  => substr((string)($data['reference'] ?? ''), 0, 191),
				'amount'     => round((float)($data['amount'] ?? 0), 4),
				'tax_amount' => round(max(0, (float)($data['tax_amount'] ?? 0)), 4),
				'currency'   => substr(strtoupper((string)($data['currency'] ?? '')), 0, 3),
				'status'     => substr((string)($data['status'] ?? ''), 0, 32),
				'note'       => isset($data['note']) ? mb_substr((string)$data['note'], 0, 1000) : '',
			]]);

			$id = (int)($result['order_transaction'] ?? 0);

			return $id ?: null;
		} catch (\Throwable $e) {
			return null;
		}
	}

	/**
	 * Ledger rows for an order, oldest first. Never throws.
	 *
	 * @param int|string $orderId Order id.
	 *
	 * @return array<int, array<string, mixed>> Transaction rows.
	 */
	public static function forOrder(int|string $orderId): array {
		try {
			$results = \Vvveb\model('order_transaction')->getByOrder([
				'order_id' => (int)$orderId,
				'start'    => 0,
				'limit'    => 500,
			]);

			return $results['order_transaction'] ?? [];
		} catch (\Throwable $e) {
			return [];
		}
	}
}
