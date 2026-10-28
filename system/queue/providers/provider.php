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
 * Interface that all queue providers must implement.
 *
 * A provider is a storage backend holding jobs for one or more named queues:
 * Redis (lists + sorted sets) or SQL database (PostgreSQL / MySQL / MariaDB / SQLite).
 * Plugins can register additional backends (Beanstalkd, SQS ...) via the
 * 'Vvveb\System\Queue\Providers', 'register' event, same as payment/notification providers.
 */
interface Provider {
	/**
	 * Push a job record onto a queue.
	 *
	 * The job record contains at least: id, queue, job, payload, attempts,
	 * max_attempts, available_at, reserved_at (null), created_at.
	 *
	 * @param string                  $queue Queue name.
	 * @param array<array-key, mixed> $job   Job record.
	 *
	 * @return string Job id.
	 */
	public function push(string $queue, array $job): string;

	/**
	 * Atomically claim and return the next available job on a queue.
	 *
	 * The claim marks the job as reserved so concurrent workers never grab
	 * the same job. Attempts counter is incremented on claim.
	 *
	 * @param string $queue Queue name.
	 *
	 * @return array<array-key, mixed>|null Job record or null when the queue is empty.
	 */
	public function pop(string $queue): ?array;

	/**
	 * Remove/acknowledge a completed job.
	 *
	 * @param string $jobId Job id.
	 *
	 * @return void
	 */
	public function complete(string $jobId): void;

	/**
	 * Return a job to the queue after an optional delay in seconds
	 * (used for retries and stalled-job requeue).
	 *
	 * @param string $jobId        Job id.
	 * @param int    $delaySeconds Delay before the job becomes available again.
	 *
	 * @return void
	 */
	public function release(string $jobId, int $delaySeconds = 0): void;

	/**
	 * Handle a failed attempt: re-queue with exponential backoff if retries
	 * remain, otherwise move the job to the failed store.
	 *
	 * @param string $jobId Job id.
	 * @param string $error Error message.
	 *
	 * @return bool True when the job was moved to the failed store (no retries left),
	 *              false when it was released back to the queue for another retry.
	 */
	public function fail(string $jobId, string $error): bool;

	/**
	 * Number of pending jobs (ready + delayed) on a queue, for admin/monitoring.
	 *
	 * @param string $queue Queue name.
	 *
	 * @return int
	 */
	public function size(string $queue): int;
}

/**
 * Extended admin/monitoring API.
 *
 * Built-in providers implement this; plugins registering custom backends are
 * encouraged to implement it too so the admin monitoring page works fully.
 */
interface FailedJobsAware {
	/**
	 * List failed jobs, newest first.
	 *
	 * @param string|null $queue Filter by queue name.
	 * @param int         $limit Max rows.
	 *
	 * @return list<array<array-key, mixed>> Failed job records (+ error, failed_at).
	 */
	public function getFailed(?string $queue = null, int $limit = 100): array;

	/**
	 * Retry a failed job: move it back to the queue with attempts reset.
	 *
	 * @param string $jobId Job id.
	 *
	 * @return bool True on success.
	 */
	public function retryFailed(string $jobId): bool;

	/**
	 * Delete a single failed job.
	 *
	 * @param string $jobId Job id.
	 *
	 * @return bool True on success.
	 */
	public function deleteFailed(string $jobId): bool;

	/**
	 * Delete all failed jobs, optionally filtered by queue.
	 *
	 * @param string|null $queue Filter by queue name.
	 *
	 * @return int Number of deleted jobs.
	 */
	public function clearFailed(?string $queue = null): int;

	/**
	 * Requeue jobs claimed by crashed workers (reserved longer than timeout).
	 *
	 * @param int $reservedTimeoutSeconds Age threshold in seconds.
	 *
	 * @return int Number of requeued jobs.
	 */
	public function requeueStalled(int $reservedTimeoutSeconds): int;

	/**
	 * Per-queue counters for the monitoring page.
	 *
	 * @param string $queue Queue name.
	 *
	 * @return array{ready: int, delayed: int, reserved: int}
	 */
	public function stats(string $queue): array;
}
