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

use function Vvveb\config;

/**
 * Redis queue provider — reliable-queue pattern.
 *
 * Keys per queue (all prefixed):
 *   {prefix}queue:{name}           list   — ready jobs (JSON records)
 *   {prefix}queue:{name}:delayed   zset   — scheduled jobs scored by available_at
 *   {prefix}queue:{name}:reserved  list   — jobs claimed by workers (in flight)
 *   {prefix}queue:{name}:failed    hash   — permanently failed jobs keyed by job id
 *
 * pop() first migrates due delayed jobs into the ready list (atomic Lua script),
 * then atomically moves a job from ready to reserved with RPOPLPUSH so a crashed
 * worker never loses its claimed job — stalled reservations are requeued by
 * requeueStalled() based on the record's reserved_at age.
 */
class Redis extends Base implements FailedJobsAware {
	public const DUE_LIMIT = 100;

	/** @var \Redis Connection instance. */
	protected \Redis $redis;

	/** @var string Key prefix. */
	protected string $prefix = 'vvveb.queue:';

	/** @var array<string, string> Claimed raw payloads by job id (for LREM on complete/fail). */
	protected array $reservedMap = [];

	/**
	 * @param array<string, mixed> $options host, port, password, database, prefix, retry.
	 */
	public function __construct(array $options = []) {
		if (!class_exists('\Redis')) {
			throw new \Exception('The phpredis extension is required for the Redis queue provider!');
		}

		//share connection settings with the cache driver when not explicitly configured
		if (empty($options['host'])) {
			$cacheConfig          = config('app.cache', []);
			$isRedis              = ($cacheConfig['driver'] ?? '') === 'redis';
			$options['host']      = $isRedis ? ($cacheConfig['host'] ?? '127.0.0.1') : '127.0.0.1';
			$options['port']      = (int)($options['port'] ?? ($isRedis ? ($cacheConfig['port'] ?? 6379) : 6379));
			$options['password']  = $options['password'] ?? ($isRedis ? ($cacheConfig['password'] ?? null) : null);
		}

		parent::__construct($options);

		$this->prefix = $this->options['prefix'] ?? $this->prefix;

		$this->redis = new \Redis();
		$this->redis->pconnect((string)$this->options['host'], (int)($this->options['port'] ?? 6379));

		if (!empty($this->options['password'])) {
			$this->redis->auth($this->options['password']);
		}

		if (isset($this->options['database'])) {
			$this->redis->select((int)$this->options['database']);
		}
	}

	/**
	 * Build a key for a queue: {prefix}{name}[:{suffix}].
	 *
	 * @param string $queue  Queue name.
	 * @param string $suffix Optional suffix ('delayed', 'reserved', 'failed').
	 *
	 * @return string
	 */
	protected function key(string $queue, string $suffix = ''): string {
		return $this->prefix . $queue . ($suffix ? ":$suffix" : '');
	}

	/**
	 * Move due delayed jobs into the ready list (atomic Lua script).
	 * Called at the start of every pop() — lightweight scheduler step.
	 *
	 * @param string $queue Queue name.
	 *
	 * @return int Number of migrated jobs.
	 */
	protected function migrateDue(string $queue): int {
		$script = <<<'LUA'
			local jobs = redis.call('ZRANGEBYSCORE', KEYS[1], '-inf', ARGV[1], 'LIMIT', 0, ARGV[2])
			local n = 0
			for _, job in ipairs(jobs) do
				redis.call('ZREM', KEYS[1], job)
				redis.call('LPUSH', KEYS[2], job)
				n = n + 1
			end
			return n
			LUA;

		try {
			return (int)$this->redis->eval($script, [$this->key($queue, 'delayed'), $this->key($queue), (string)time(), self::DUE_LIMIT], 2);
		} catch (\Throwable $e) {
			//fallback without Lua (non-atomic but safe enough for low concurrency)
			$jobs = $this->redis->zRangeByScore($this->key($queue, 'delayed'), '-inf', (string)time(), ['limit' => [0, self::DUE_LIMIT]]);
			$n    = 0;

			foreach ($jobs as $job) {
				if ($this->redis->zRem($this->key($queue, 'delayed'), $job) && $this->redis->lPush($this->key($queue), $job)) {
					$n++;
				}
			}

			return $n;
		}
	}

	/**
	 * Push a raw JSON record to ready/delayed depending on availability.
	 *
	 * @param string                  $queue Queue name.
	 * @param array<array-key, mixed> $job   Job record.
	 *
	 * @return void
	 */
	protected function pushRaw(string $queue, array $job): void {
		$json        = json_encode($job);
		$availableAt = (int)($job['available_at'] ?? time());

		if ($availableAt <= time()) {
			$this->redis->lPush($this->key($queue), $json);
		} else {
			$this->redis->zAdd($this->key($queue, 'delayed'), $availableAt, $json);
		}
	}

	/**
	 * Find the raw payload stored in the reserved list for a claimed job id.
	 *
	 * @param string      $jobId Job id.
	 * @param string|null $queue Queue name hint (scans all queues when null).
	 *
	 * @return string|null Raw JSON payload.
	 */
	protected function findReservedRaw(string $jobId, ?string $queue = null): ?string {
		if (isset($this->reservedMap[$jobId])) {
			return $this->reservedMap[$jobId];
		}

		if ($queue) {
			$key = $this->key($queue, 'reserved');
			$len = $this->redis->lLen($key);

			for ($i = 0; $i < min(1000, $len); $i++) {
				$raw = $this->redis->lIndex($key, $i);

				if (!$raw) {
					continue;
				}

				$job = json_decode($raw, true);

				if (($job['id'] ?? null) === $jobId) {
					return $raw;
				}
			}
		}

		return null;
	}

	/**
	 * Remove a raw payload from a queue's reserved list.
	 *
	 * @param string $queue Queue name.
	 * @param string $raw   Raw JSON payload.
	 *
	 * @return void
	 */
	protected function removeReservedRaw(string $queue, string $raw): void {
		$this->redis->lRem($this->key($queue, 'reserved'), $raw, 1);
	}

	/**
	 * @param string                  $queue Queue name.
	 * @param array<array-key, mixed> $job   Job record.
	 *
	 * @return string Job id.
	 */
	public function push(string $queue, array $job): string {
		$this->pushRaw($queue, $job);

		return (string)$job['id'];
	}

	/**
	 * Atomically claim and return the next available job.
	 *
	 * @param string $queue Queue name.
	 *
	 * @return array<array-key, mixed>|null
	 */
	public function pop(string $queue): ?array {
		$this->migrateDue($queue);

		$readyKey    = $this->key($queue);
		$reservedKey = $this->key($queue, 'reserved');

		$raw = $this->redis->rPopLPush($readyKey, $reservedKey);

		if (!$raw) {
			return null;
		}

		$job = json_decode($raw, true);

		if (!is_array($job)) {
			//corrupt record — drop it from reserved so it doesn't loop forever
			$this->removeReservedRaw($queue, $raw);

			return null;
		}

		//mark claim: bump attempts + stamp reserved_at, replace record in reserved list
		$job['attempts']     = (int)($job['attempts'] ?? 0) + 1;
		$job['reserved_at']  = time();
		$newRaw              = json_encode($job);

		$this->removeReservedRaw($queue, $raw);
		$this->redis->lPush($reservedKey, $newRaw);

		$this->reservedMap[(string)$job['id']] = $newRaw;

		return $this->normalize($job);
	}

	/**
	 * Acknowledge a completed job.
	 *
	 * @param string $jobId Job id.
	 *
	 * @return void
	 */
	public function complete(string $jobId): void {
		$raw = $this->findReservedRaw($jobId);

		if ($raw !== null) {
			$job = json_decode($raw, true);
			$this->removeReservedRaw($job['queue'] ?? 'default', $raw);
			unset($this->reservedMap[$jobId]);
		}
	}

	/**
	 * Return a job to the queue after an optional delay.
	 *
	 * @param string $jobId        Job id.
	 * @param int    $delaySeconds Delay before job becomes available again.
	 *
	 * @return void
	 */
	public function release(string $jobId, int $delaySeconds = 0): void {
		$raw = $this->findReservedRaw($jobId);

		if ($raw === null) {
			return;
		}

		$job                 = json_decode($raw, true);
		$queue               = $job['queue'] ?? 'default';
		$job['reserved_at']  = null;
		$job['available_at'] = time() + max(0, $delaySeconds);

		$this->removeReservedRaw($queue, $raw);
		unset($this->reservedMap[$jobId]);

		$this->pushRaw($queue, $job);
	}

	/**
	 * Handle failure: release with exponential backoff while retries remain,
	 * otherwise move to the failed hash.
	 *
	 * @param string $jobId Job id.
	 * @param string $error Error message.
	 *
	 * @return bool True when moved to failed store, false when released for retry.
	 */
	public function fail(string $jobId, string $error): bool {
		$raw = $this->findReservedRaw($jobId);

		if ($raw === null) {
			return false;
		}

		$job   = json_decode($raw, true);
		$queue = $job['queue'] ?? 'default';

		$this->removeReservedRaw($queue, $raw);
		unset($this->reservedMap[$jobId]);

		if ((int)($job['attempts'] ?? 0) < (int)($job['max_attempts'] ?? 3)) {
			$job['available_at'] = time() + $this->backoffDelay((int)$job['attempts']);
			$this->pushRaw($queue, $job);

			return false;
		}

		$job['error']     = mb_substr($error, 0, 65535);
		$job['failed_at'] = time();

		$this->redis->hSet($this->key($queue, 'failed'), $jobId, json_encode($job));

		return true;
	}

	/**
	 * Pending (ready + delayed) job count on a queue.
	 *
	 * @param string $queue Queue name.
	 *
	 * @return int
	 */
	public function size(string $queue): int {
		return (int)$this->redis->lLen($this->key($queue)) + (int)$this->redis->zCard($this->key($queue, 'delayed'));
	}

	/**
	 * Per-queue counters for monitoring.
	 *
	 * @param string $queue Queue name.
	 *
	 * @return array{ready: int, delayed: int, reserved: int}
	 */
	public function stats(string $queue): array {
		return [
			'ready'    => (int)$this->redis->lLen($this->key($queue)),
			'delayed'  => (int)$this->redis->zCard($this->key($queue, 'delayed')),
			'reserved' => (int)$this->redis->lLen($this->key($queue, 'reserved')),
		];
	}

	/**
	 * Requeue jobs claimed by crashed workers (reserved_at older than timeout).
	 *
	 * @param int $reservedTimeoutSeconds Age threshold in seconds.
	 *
	 * @return int Number of requeued jobs.
	 */
	public function requeueStalled(int $reservedTimeoutSeconds): int {
		$cutoff = time() - max(1, $reservedTimeoutSeconds);
		$count  = 0;

		//scan every known queue's reserved list
		$iterator = null;
		$pattern  = $this->prefix . '*:reserved';

		while ($keys = $this->redis->scan($iterator, $pattern)) {
			foreach ($keys as $key) {
				$len = (int)$this->redis->lLen($key);

				for ($i = 0; $i < min(1000, $len); $i++) {
					$raw = $this->redis->lIndex($key, $i);

					if (!$raw) {
						continue;
					}

					$job = json_decode($raw, true);

					if (!is_array($job) || empty($job['reserved_at']) || $job['reserved_at'] > $cutoff) {
						continue;
					}

					$queue               = $job['queue'] ?? 'default';
					$job['reserved_at']  = null;
					$job['available_at'] = time();

					$this->redis->lRem($key, $raw, 1);
					$this->pushRaw($queue, $job);
					$count++;
				}
			}
		}

		return $count;
	}

	// ── Failed jobs ────────────────────────────────────────────────────

	/**
	 * List failed jobs, newest first.
	 *
	 * @param string|null $queue Filter by queue.
	 * @param int         $limit Max rows.
	 *
	 * @return list<array<array-key, mixed>>
	 */
	public function getFailed(?string $queue = null, int $limit = 100): array {
		$result = [];

		if ($queue) {
			$keys = [$queue => $this->key($queue, 'failed')];
		} else {
			$keys = [];

			$iterator = null;
			$pattern  = $this->prefix . '*:failed';

			while ($found = $this->redis->scan($iterator, $pattern)) {
				foreach ($found as $key) {
					$name         = str_replace([$this->prefix, ':failed'], '', $key);
					$keys[$name]  = $key;
				}
			}
		}

		foreach ($keys as $name => $key) {
			$jobs = $this->redis->hGetAll($key) ?: [];

			foreach ($jobs as $raw) {
				$job = json_decode($raw, true);

				if (is_array($job)) {
					$job['queue'] = $name;
					$result[]     = $job;
				}
			}
		}

		usort($result, fn ($a, $b) => (int)($b['failed_at'] ?? 0) <=> (int)($a['failed_at'] ?? 0));

		return array_slice($result, 0, $limit);
	}

	/**
	 * Move a failed job back into the queue with attempts reset.
	 *
	 * @param string $jobId Job id.
	 *
	 * @return bool
	 */
	public function retryFailed(string $jobId): bool {
		$raw       = null;
		$queueName = null;

		$iterator = null;
		$pattern  = $this->prefix . '*:failed';

		while ($found = $this->redis->scan($iterator, $pattern)) {
			foreach ($found as $key) {
				$raw = $this->redis->hGet($key, $jobId);

				if ($raw) {
					$queueName = str_replace([$this->prefix, ':failed'], '', $key);

					break 2;
				}
			}
		}

		if (!$raw || !$queueName) {
			return false;
		}

		$job = json_decode($raw, true);

		$this->redis->hDel($this->key($queueName, 'failed'), $jobId);

		$job['attempts']     = 0;
		$job['available_at'] = time();
		unset($job['error'], $job['failed_at']);

		$this->pushRaw($queueName, $job);

		return true;
	}

	/**
	 * Delete a single failed job.
	 *
	 * @param string $jobId Job id.
	 *
	 * @return bool
	 */
	public function deleteFailed(string $jobId): bool {
		$deleted = 0;

		$iterator = null;
		$pattern  = $this->prefix . '*:failed';

		while ($found = $this->redis->scan($iterator, $pattern)) {
			foreach ($found as $key) {
				$deleted += (int)$this->redis->hDel($key, $jobId);
			}
		}

		return $deleted > 0;
	}

	/**
	 * Delete all failed jobs, optionally filtered by queue.
	 *
	 * @param string|null $queue Filter by queue.
	 *
	 * @return int Deleted count.
	 */
	public function clearFailed(?string $queue = null): int {
		if ($queue) {
			$key = $this->key($queue, 'failed');
			$cnt = (int)$this->redis->hLen($key);
			$this->redis->del($key);

			return $cnt;
		}

		$total = 0;

		$iterator = null;
		$pattern  = $this->prefix . '*:failed';

		while ($found = $this->redis->scan($iterator, $pattern)) {
			foreach ($found as $key) {
				$total += (int)$this->redis->hLen($key);
				$this->redis->del($key);
			}
		}

		return $total;
	}
}
