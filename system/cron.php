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

class Cron {
	/**
	 * @var array<int, array<int, array<string, array{0: callable, 1: mixed, 2: ?string}>>>
	 *                                                                                      Registered cron jobs grouped by interval (seconds) and priority.
	 *                                                                                      Each job entry is [callback, start, description].
	 */
	private static array $crons = [];

	/**
	 * Get singleton instance of the Cron class.
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
	 * Get all registered cron jobs.
	 *
	 * @return array<int, array<int, array<string, array{0: callable, 1: mixed, 2: ?string}>>>
	 *                                                                                         Cron jobs grouped by interval (seconds) and priority.
	 */
	public static function getCrons(): array {
		return self::$crons;
	}

	/**
	 * Run all registered cron jobs.
	 *
	 * @return array<string, array<int, mixed>> Cron name => result data.
	 */
	public static function runAll(): array {
		$results = [];

		foreach (self::$crons as $priorities) {
			foreach ($priorities as $jobs) {
				foreach (array_keys($jobs) as $cron) {
					$results[$cron] = self::run($cron);
				}
			}
		}

		return $results;
	}

	/**
	 * Run a single cron job by name.
	 *
	 * @param string $cron Cron job name (namespace-name).
	 *
	 * @return array<array-key, mixed> Result data.
	 */
	public static function run(string $cron): array {
		foreach (self::$crons as $priorities) {
			foreach ($priorities as $jobs) {
				if (!isset($jobs[$cron])) {
					continue;
				}

				[$callback, $start] = $jobs[$cron];

				if (!self::isDue($start)) {
					return [];
				}

				try {
					return (array)$callback($cron);
				} catch (\Throwable $e) {
					\Vvveb\logError(sprintf('Cron job "%s" failed: %s', $cron, $e->getMessage()));

					try {
						Notifications::trigger('cronjob.fail', [
							'subject' => sprintf('Cron job "%s" failed', $cron),
							'message' => $e->getMessage(),
							'cron'    => $cron,
							'error'   => $e->getMessage(),
						]);
					} catch (\Throwable $ignored) {
						// notifications must never break cron execution
					}

					return ['error' => $e->getMessage()];
				}
			}
		}

		return [];
	}

	/**
	 * Check whether a cron job start time has been reached.
	 *
	 * @param mixed $start Timestamp, datetime string or null (run immediately).
	 *
	 * @return bool
	 */
	private static function isDue($start): bool {
		if (!$start) {
			return true;
		}

		$time = is_numeric($start) ? (int)$start : strtotime((string)$start);

		return $time === false || $time <= time();
	}

	/**
	 * Register a cron job.
	 *
	 * @param string          $namespace   Plugin name
	 * @param string          $name        Action name
	 * @param callable        $callback    Code to run
	 * @param int             $priority    Priority
	 * @param int             $interval    Default every minute
	 * @param int|string|null $start       Start timestamp or datetime string, null = immediately
	 * @param string|null     $description Human-readable description.
	 *
	 * @return bool Always returns true.
	 */
	public static function registerCron(string $namespace, string $name, callable $callback, int $priority = 1, int $interval = 60, $start = null, ?string $description = null): bool {
		$key = $namespace . '-' . $name;
		if (empty(self::$crons[$interval])) {
			self::$crons[$interval][$priority] = [];
		}

		self::$crons[$interval][$priority][$key] = [$callback, $start, $description];
		ksort(self::$crons[$interval], SORT_NUMERIC);

		return true;
	}
}
