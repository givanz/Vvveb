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

use function Vvveb\__;
use function Vvveb\config;
use function Vvveb\logError;
use Vvveb\System\Queue\Providers\Base;
use Vvveb\System\Queue\Providers\Database;
use Vvveb\System\Queue\Providers\FailedJobsAware;
use Vvveb\System\Queue\Providers\Provider;
use Vvveb\System\Queue\Providers\Redis;

/**
 * Background job queue.
 *
 * Push jobs onto named queues and process them from a worker
 * (`php cli.php queue:work`) or a cron single-pass (`module=cron`).
 *
 * Backends are providers behind a common interface; Redis and database
 * (PostgreSQL / MySQL / MariaDB / SQLite) ship built in, plugins can register
 * more through the 'Vvveb\System\Queue\Providers', 'register' event 
 *
 * Usage:
 *
 *   // register a handler (eg. during plugin bootstrap)
 *   \Vvveb\Queue::registerHandler('SendOrderConfirmation', function ($payload, $job) { ... });
 *
 *   // enqueue
 *   \Vvveb\Queue::push('default', 'SendOrderConfirmation', ['order_id' => 10]);
 *   \Vvveb\Queue::later('default', 3600, 'SendReminder', ['user_id' => 5]);
 */
class Queue {
	public const DEFAULT_QUEUE = 'default';

	/** @var Provider|null Active provider instance. */
	protected static ?Provider $provider = null;

	/** @var array<string, callable> Registered job handlers keyed by job name. */
	protected static array $handlers = [];

	/**
	 * Get the active provider instance based on config/queue.php,
	 * letting plugins register additional backends.
	 *
	 * @return Provider
	 */
	public static function getProvider(): Provider {
		if (static::$provider) {
			return static::$provider;
		}

		$config    = config('queue', []);
		$providers = [
			'database' => Database::class,
			'redis'    => Redis::class,
		];

		//let plugins register additional backends (same as payment/notification providers)
		[$providers] = Event::trigger('Vvveb\System\Queue\Providers', 'register', $providers);

		$name = $config['default'] ?? 'database';

		if (!isset($providers[$name]) || !class_exists($providers[$name])) {
			//fall back to database when the configured backend is missing
			$name = isset($providers['database']) ? 'database' : array_key_first($providers);
		}

		$options          = $config['providers'][$name] ?? [];
		$options['retry'] = $config['retry'] ?? [];

		static::$provider = new $providers[$name]($options);

		return static::$provider;
	}

	/**
	 * Override the provider instance (used by tests and plugins).
	 *
	 * @param Provider $provider
	 *
	 * @return void
	 */
	public static function setProvider(Provider $provider): void {
		static::$provider = $provider;
	}

	/**
	 * Push a job onto a queue. Payload must be serializable (array/scalar).
	 *
	 * Options: max_attempts (int), available_at (unix ts), delay (seconds).
	 *
	 * @param string                  $queue   Queue name.
	 * @param string                  $job     Job name (a handler must be registered for it).
	 * @param array<array-key, mixed> $payload Job payload.
	 * @param array<array-key, mixed> $options max_attempts, available_at, delay.
	 *
	 * @return string Job id.
	 */
	public static function push(string $queue, string $job, array $payload = [], array $options = []): string {
		$config       = config('queue', []);
		$availableAt  = isset($options['available_at']) ? (int)$options['available_at']
			: (isset($options['delay']) ? time() + (int)$options['delay'] : time());

		$record = [
			'id'           => Base::uuid(),
			'queue'        => $queue,
			'job'          => $job,
			'payload'      => $payload,
			'attempts'     => 0,
			'max_attempts' => (int)($options['max_attempts'] ?? $config['max_attempts'] ?? 3),
			'available_at' => $availableAt,
			'reserved_at'  => null,
			'created_at'   => time(),
		];

		Event::trigger(self::class, 'push:before', $record, $options);

		return static::getProvider()->push($queue, $record);
	}

	/**
	 * Push a job to run after a delay in seconds.
	 *
	 * @param string                  $queue        Queue name.
	 * @param int                     $delaySeconds Delay before the job becomes available.
	 * @param string                  $job          Job name.
	 * @param array<array-key, mixed> $payload      Job payload.
	 * @param array<array-key, mixed> $options      Extra options passed to push().
	 *
	 * @return string Job id.
	 */
	public static function later(string $queue, int $delaySeconds, string $job, array $payload = [], array $options = []): string {
		$options['delay'] = $delaySeconds;

		return static::push($queue, $job, $payload, $options);
	}

	/**
	 * Pop and return the next available job on a queue (claimed by this worker).
	 *
	 * @param string $queue Queue name.
	 *
	 * @return array<array-key, mixed>|null Job record or null if empty.
	 */
	public static function pop(string $queue): ?array {
		return static::getProvider()->pop($queue);
	}

	/**
	 * Mark a job as completed.
	 *
	 * @param string $jobId Job id.
	 *
	 * @return void
	 */
	public static function complete(string $jobId): void {
		static::getProvider()->complete($jobId);
	}

	/**
	 * Mark a job as failed; re-queues it with exponential backoff if retries
	 * remain, otherwise moves it to the failed store and triggers the
	 * 'queue.job.failed' notification event.
	 *
	 * @param string      $jobId Job id.
	 * @param string|null $error Error message.
	 *
	 * @return bool True when the job died permanently (moved to failed store).
	 */
	public static function fail(string $jobId, ?string $error = null): bool {
		$failed = static::getProvider()->fail($jobId, $error ?? 'Unknown error');

		if ($failed) {
			try {
				Notifications::trigger('queue.job.failed', [
					'job_id'  => $jobId,
					'error'   => $error,
					'subject' => __('Background job failed'),
					'message' => sprintf(__('Job %s failed permanently: %s'), $jobId, $error),
				]);
			} catch (\Throwable $e) {
				//never let notifications break the worker
			}
		}

		return $failed;
	}

	/**
	 * Return a claimed job to the queue after an optional delay.
	 *
	 * @param string $jobId        Job id.
	 * @param int    $delaySeconds Delay before the job becomes available again.
	 *
	 * @return void
	 */
	public static function release(string $jobId, int $delaySeconds = 0): void {
		static::getProvider()->release($jobId, $delaySeconds);
	}

	/**
	 * Pending (ready + delayed) job count on a queue.
	 *
	 * @param string $queue Queue name.
	 *
	 * @return int
	 */
	public static function size(string $queue): int {
		return static::getProvider()->size($queue);
	}

	/**
	 * Register a handler for a job name so the worker knows how to run it.
	 *
	 * The handler receives ($payload, $job) where $payload is the job payload
	 * array and $job the full job record. Throw inside the handler to fail the job.
	 *
	 * @param string   $job     Job name.
	 * @param callable $handler function (array $payload, array $job): void
	 *
	 * @return void
	 */
	public static function registerHandler(string $job, callable $handler): void {
		static::$handlers[$job] = $handler;
	}

	/**
	 * Get the registered handler for a job name.
	 *
	 * @param string $job Job name.
	 *
	 * @return callable|null
	 */
	public static function getHandler(string $job): ?callable {
		return static::$handlers[$job] ?? null;
	}

	/**
	 * Run a popped job against its registered handler, catching any error so a
	 * bad job never kills the worker loop; completes or fails the job accordingly.
	 *
	 * @param array<array-key, mixed> $job Job record.
	 *
	 * @return array{ok: bool, error?: string}
	 */
	public static function runJob(array $job): array {
		$handler = static::getHandler($job['job']);

		Event::trigger(self::class, 'job:before', $job);

		try {
			if (!$handler) {
				throw new \Exception(sprintf(__('No handler registered for job "%s"!'), $job['job']));
			}

			$handler($job['payload'], $job);
		} catch (\Throwable $e) {
			$error = $e->getMessage();

			try {
				logError(sprintf('[Queue] Job %s (%s) failed: %s', $job['id'], $job['job'], $error));
				static::fail($job['id'], $error);
			} catch (\Throwable $ignored) {
			}

			Event::trigger(self::class, 'job:error', $job, $error);

			return ['ok' => false, 'error' => $error];
		}

		try {
			static::complete($job['id']);
		} catch (\Throwable $e) {
			logError(sprintf('[Queue] Completing job %s failed: %s', $job['id'], $e->getMessage()));
		}

		Event::trigger(self::class, 'job:after', $job);

		return ['ok' => true];
	}

	/**
	 * Worker loop: pop → run registered handler → complete/fail.
	 *
	 * @param string|null $queue   Queue name, null = default queue.
	 * @param int         $maxJobs Stop after N processed jobs, 0 = run forever.
	 * @param int         $sleep   Seconds to sleep between empty polls.
	 * @param bool        $drain   Stop as soon as the queue is empty (cron mode)
	 *                             instead of polling forever.
	 *
	 * @return array{processed: int, ok: int, failed: int} Counters.
	 */
	public static function work(?string $queue = null, int $maxJobs = 0, int $sleep = 1, bool $drain = false): array {
		$queue           = $queue ?: self::DEFAULT_QUEUE;
		$sleep           = max(0, $sleep);
		$counters        = ['processed' => 0, 'ok' => 0, 'failed' => 0];
		$lastReap        = time();
		$reservedTimeout = (int)(config('queue.reserved_timeout_seconds', 300));

		while ($maxJobs === 0 || $counters['processed'] < $maxJobs) {
			//periodically requeue jobs stranded by crashed workers
			if (time() - $lastReap >= 60) {
				$lastReap = time();
				static::reapStalledJobs($reservedTimeout);
			}

			try {
				$job = static::pop($queue);
			} catch (\Throwable $e) {
				logError('[Queue] Popping failed: ' . $e->getMessage());

				if ($drain) {
					break;
				}

				sleep(max(1, $sleep));

				continue;
			}

			if (!$job) {
				if ($drain) {
					break;
				}

				if ($sleep > 0) {
					sleep($sleep);
				}

				continue;
			}

			$result            = static::runJob($job);
			$counters['processed']++;

			if ($result['ok']) {
				$counters['ok']++;
			} else {
				$counters['failed']++;
			}

			if (function_exists('pcntl_signal_dispatch')) {
				pcntl_signal_dispatch();
			}
		}

		return $counters;
	}

	/**
	 * Single short pass for the cron mechanism (shared hosting without a
	 * persistent worker): drain up to $maxJobs jobs or stop after $maxSeconds.
	 *
	 * @return array{processed: int, ok: int, failed: int}
	 */
	public static function cronPass(): array {
		$config = config('queue.cron_pass', []);

		if (isset($config['enabled']) && !$config['enabled']) {
			return ['processed' => 0, 'ok' => 0, 'failed' => 0];
		}

		$maxJobs    = (int)($config['max_jobs'] ?? 25);
		$maxSeconds = (int)($config['max_seconds'] ?? 20);
		$start      = time();
		$queue      = self::DEFAULT_QUEUE;
		$counters   = ['processed' => 0, 'ok' => 0, 'failed' => 0];

		static::reapStalledJobs((int)(config('queue.reserved_timeout_seconds', 300)));

		while ($counters['processed'] < $maxJobs && (time() - $start) < $maxSeconds) {
			try {
				$job = static::pop($queue);
			} catch (\Throwable $e) {
				logError('[Queue] Cron pass pop failed: ' . $e->getMessage());

				break;
			}

			if (!$job) {
				break;
			}

			$result           = static::runJob($job);
			$counters['processed']++;

			if ($result['ok']) {
				$counters['ok']++;
			} else {
				$counters['failed']++;
			}
		}

		return $counters;
	}

	/**
	 * Requeue jobs whose reservation expired (crashed worker cleanup).
	 *
	 * @param int|null $reservedTimeoutSeconds Defaults to queue config value.
	 *
	 * @return int Number of requeued jobs.
	 */
	public static function reapStalledJobs(?int $reservedTimeoutSeconds = null): int {
		$provider = static::getProvider();

		if (!$provider instanceof FailedJobsAware) {
			return 0;
		}

		try {
			return $provider->requeueStalled(
				$reservedTimeoutSeconds ?? (int)(config('queue.reserved_timeout_seconds', 300))
			);
		} catch (\Throwable $e) {
			logError('[Queue] Stalled-job reaper failed: ' . $e->getMessage());

			return 0;
		}
	}

	/**
	 * List failed jobs (when the active provider supports it).
	 *
	 * @param string|null $queue Filter by queue.
	 * @param int         $limit Max rows.
	 *
	 * @return list<array<array-key, mixed>>
	 */
	public static function failedJobs(?string $queue = null, int $limit = 100): array {
		$provider = static::getProvider();

		if (!$provider instanceof FailedJobsAware) {
			return [];
		}

		return $provider->getFailed($queue, $limit);
	}

	/**
	 * Retry a failed job (when the active provider supports it).
	 *
	 * @param string $jobId Job id.
	 *
	 * @return bool
	 */
	public static function retryFailedJob(string $jobId): bool {
		$provider = static::getProvider();

		return ($provider instanceof FailedJobsAware) ? $provider->retryFailed($jobId) : false;
	}

	/**
	 * Delete a failed job (when the active provider supports it).
	 *
	 * @param string $jobId Job id.
	 *
	 * @return bool
	 */
	public static function deleteFailedJob(string $jobId): bool {
		$provider = static::getProvider();

		return ($provider instanceof FailedJobsAware) ? $provider->deleteFailed($jobId) : false;
	}

	/**
	 * Clear all failed jobs (when the active provider supports it).
	 *
	 * @param string|null $queue Filter by queue.
	 *
	 * @return int Deleted count.
	 */
	public static function clearFailedJobs(?string $queue = null): int {
		$provider = static::getProvider();

		return ($provider instanceof FailedJobsAware) ? $provider->clearFailed($queue) : 0;
	}

	/**
	 * Per-queue counters for monitoring (when the active provider supports it).
	 *
	 * @param string $queue Queue name.
	 *
	 * @return array{ready: int, delayed: int, reserved: int}
	 */
	public static function stats(string $queue): array {
		$provider = static::getProvider();

		return ($provider instanceof FailedJobsAware) ? $provider->stats($queue) :
			['ready' => static::size($queue), 'delayed' => 0, 'reserved' => 0];
	}

	/**
	 * Human readable name of the active provider ('database', 'redis', ...).
	 *
	 * @return string
	 */
	public static function providerName(): string {
		$config = config('queue', []);

		return $config['default'] ?? 'database';
	}
}
