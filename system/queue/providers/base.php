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

namespace Vvveb\System\Queue\Providers;

/**
 * Shared helpers for queue providers: retry backoff computation,
 * job id generation and job record normalization.
 */
abstract class Base implements Provider {
	/** @var array<string, mixed> Provider options from config/queue.php. */
	protected array $options = [];

	/** @var array{base_delay_seconds: int, max_delay_seconds: int} Retry backoff settings. */
	protected array $retry = [
		'base_delay_seconds' => 30,
		'max_delay_seconds'  => 3600,
	];

	/**
	 * @param array<string, mixed> $options Provider options (retry, ...).
	 */
	public function __construct(array $options = []) {
		$this->options = $options;
		$this->retry   = array_merge($this->retry, $options['retry'] ?? []);
	}

	/**
	 * Exponential backoff delay for attempt N: base * 2^attempts, capped.
	 *
	 * @param int $attempts Number of attempts used so far.
	 *
	 * @return int Delay in seconds.
	 */
	protected function backoffDelay(int $attempts): int {
		$base = max(1, (int)$this->retry['base_delay_seconds']);
		$cap  = max($base, (int)$this->retry['max_delay_seconds']);
		$sec  = $base * (2 ** max(0, $attempts));

		return min($cap, $sec);
	}

	/**
	 * Generate a UUID v4 job id.
	 *
	 * @return string
	 */
	public static function uuid(): string {
		$data = random_bytes(16);

		//set version to 0100 and IETF variant to 10
		$data[6] = chr((ord($data[6]) & 0x0F) | 0x40);
		$data[8] = chr((ord($data[8]) & 0x3F) | 0x80);

		return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
	}

	/**
	 * Normalize a raw storage row into the standard job record shape:
	 * integer counters/timestamps and an decoded payload array.
	 *
	 * @param array<string, mixed> $row Raw row; payload may still be encoded JSON.
	 *
	 * @return array<array-key, mixed> Job record.
	 */
	protected function normalize(array $row): array {
		$payload = $row['payload'] ?? [];

		if (is_string($payload)) {
			$payload = json_decode($payload, true);
		}

		return [
			'id'           => (string)($row['job_id'] ?? $row['id'] ?? ''),
			'queue'        => (string)($row['queue'] ?? 'default'),
			'job'          => (string)($row['job'] ?? ''),
			'payload'      => is_array($payload) ? $payload : [],
			'attempts'     => (int)($row['attempts'] ?? 0),
			'max_attempts' => (int)($row['max_attempts'] ?? 3),
			'available_at' => (int)($row['available_at'] ?? 0),
			'reserved_at'  => isset($row['reserved_at']) && $row['reserved_at'] !== ''
				? (int)$row['reserved_at'] : null,
			'created_at' => (int)($row['created_at'] ?? 0),
		];
	}
}
