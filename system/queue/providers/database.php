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

use Vvveb\System\Db;

/**
 * Database queue provider.
 *
 * Stores jobs in `queue_jobs` / `failed_jobs` tables using Vvveb's existing
 * DB connection — no separate configuration needed. Tables have a numeric
 * auto-increment `id` plus a public `job_id` UUID; the claim dialect is picked
 * automatically from the configured engine and server version:
 *
 * - PostgreSQL: single-statement atomic claim using `FOR UPDATE SKIP LOCKED`
 *   in the subquery + `RETURNING`, letting multiple workers poll concurrently
 *   without blocking or double-claiming.
 * - MySQL / MariaDB 8.0+ / 10.6+: transactional
 *   `SELECT ... FOR UPDATE SKIP LOCKED` + `UPDATE` by id — workers skip rows
 *   locked by other workers instead of waiting.
 *   Older servers fall back to an inherently-atomic single-statement claim
 *   using the `LAST_INSERT_ID(id)` trick to read back which row was claimed
 *   (no explicit transaction needed).
 * - SQLite: no row-level locking (single writer by design) — a plain claim
 *   inside a `BEGIN IMMEDIATE` transaction serializes concurrent workers
 *   cleanly. Best paired with a single worker process / cron mode.
 */
class Database extends Base implements FailedJobsAware {
	/** @var object Db driver instance (shared Vvveb connection). */
	protected object $db;

	/** @var string Active dialect: pgsql | mysql | sqlite. */
	protected string $driver = 'mysql';

	/** @var string Jobs table name (with prefix). */
	protected string $jobsTable = 'queue_jobs';

	/** @var string Failed jobs table name (with prefix). */
	protected string $failedTable = 'failed_jobs';

	/** @var bool Whether schema has been checked for this process. */
	protected static bool $schemaChecked = false;

	/** @var bool|null Server supports FOR UPDATE SKIP LOCKED (MySQL 8.0+ / MariaDB 10.6+). */
	protected static ?bool $skipLocked = null;

	/** @var string Comma-separated columns selected when reading back a job row. */
	protected const ROW_COLUMNS = 'id, job_id, queue, job, payload, attempts, max_attempts, available_at, reserved_at, created_at';

	/**
	 * @param array<string, mixed> $options Provider options (unused, kept for symmetry;
	 *                                      the DB connection comes from config/db.php).
	 */
	public function __construct(array $options = []) {
		parent::__construct($options);

		$this->db = Db::getInstance();

		$engine       = defined('DB_ENGINE') ? strtolower((string)DB_ENGINE) : 'mysqli';
		$this->driver = match ($engine) {
			'pgsql'  => 'pgsql',
			'sqlite' => 'sqlite',
			default  => 'mysql',
		};

		$prefix            = $this->db->prefix ?? DB_PREFIX;
		$this->jobsTable   = $prefix . 'queue_jobs';
		$this->failedTable = $prefix . 'failed_jobs';

		if ($this->driver === 'sqlite') {
			//serialize concurrent writers instead of failing with SQLITE_BUSY
			try {
				$this->db->query('PRAGMA busy_timeout = 5000');
				@$this->db->query('PRAGMA journal_mode = WAL');
			} catch (\Throwable $e) {
				//ignore, best effort
			}
		}
	}

	/**
	 * Check whether the MySQL/MariaDB server supports FOR UPDATE SKIP LOCKED
	 * (MySQL 8.0+, MariaDB 10.6+). Cached per process.
	 *
	 * @return bool
	 */
	protected function supportsSkipLocked(): bool {
		if (static::$skipLocked !== null) {
			return static::$skipLocked;
		}

		static::$skipLocked = false;

		try {
			$stmt = $this->db->execute('SELECT VERSION()');
			$rows = $this->db->fetchAll($stmt);
			$ver  = (string)($rows[0]['VERSION()'] ?? $rows[0][array_key_first($rows[0])] ?? '');

			if (stripos($ver, 'mariadb') !== false) {
				//eg. 10.11.14-MariaDB
				static::$skipLocked = version_compare($ver, '10.6', '>=');
			} else {
				//eg. 8.0.36
				static::$skipLocked = version_compare($ver, '8.0.1', '>=');
			}
		} catch (\Throwable $e) {
			if (function_exists('logError')) {
				logError('[Queue] Version check failed, using fallback claim: ' . $e->getMessage());
			}
		}

		return static::$skipLocked;
	}

	/**
	 * Create the queue tables if missing (once per process).
	 * Lets existing installations upgrade without re-running the installer.
	 *
	 * @return void
	 */
	protected function ensureSchema(): void {
		if (self::$schemaChecked) {
			return;
		}

		self::$schemaChecked = true;

		switch ($this->driver) {
			case 'pgsql':
				$sql[] = "CREATE TABLE IF NOT EXISTS {$this->jobsTable} (
					id bigserial PRIMARY KEY,
					\"job_id\" varchar(36) NOT NULL,
					\"queue\" varchar(128) NOT NULL DEFAULT 'default',
					\"job\" varchar(255) NOT NULL,
					\"payload\" text,
					attempts integer NOT NULL DEFAULT 0,
					max_attempts integer NOT NULL DEFAULT 3,
					available_at bigint NOT NULL DEFAULT 0,
					reserved_at bigint DEFAULT NULL,
					created_at bigint NOT NULL DEFAULT 0
				)";
				$sql[] = "CREATE TABLE IF NOT EXISTS {$this->failedTable} (
					\"job_id\" varchar(36) NOT NULL PRIMARY KEY,
					\"queue\" varchar(128) NOT NULL DEFAULT 'default',
					\"job\" varchar(255) NOT NULL,
					\"payload\" text,
					error text,
					attempts integer NOT NULL DEFAULT 0,
					max_attempts integer NOT NULL DEFAULT 3,
					failed_at bigint NOT NULL DEFAULT 0
				)";
				$sql[] = 'CREATE UNIQUE INDEX IF NOT EXISTS u_queue_jobs_job_id ON ' . $this->jobsTable . ' ("job_id")';
				$sql[] = 'CREATE INDEX IF NOT EXISTS idx_queue_jobs_queue_available ON ' . $this->jobsTable . ' ("queue", "available_at")';
				$sql[] = 'CREATE INDEX IF NOT EXISTS idx_queue_jobs_reserved ON ' . $this->jobsTable . ' ("reserved_at")';
				break;

			case 'sqlite':
				$sql[] = "CREATE TABLE IF NOT EXISTS `{$this->jobsTable}` (
					id INTEGER PRIMARY KEY AUTOINCREMENT,
					job_id TEXT NOT NULL UNIQUE,
					queue TEXT NOT NULL DEFAULT 'default',
					job TEXT NOT NULL,
					payload TEXT,
					attempts INTEGER NOT NULL DEFAULT 0,
					max_attempts INTEGER NOT NULL DEFAULT 3,
					available_at INTEGER NOT NULL DEFAULT 0,
					reserved_at INTEGER DEFAULT NULL,
					created_at INTEGER NOT NULL DEFAULT 0
				)";
				$sql[] = "CREATE TABLE IF NOT EXISTS `{$this->failedTable}` (
					job_id TEXT NOT NULL PRIMARY KEY,
					queue TEXT NOT NULL DEFAULT 'default',
					job TEXT NOT NULL,
					payload TEXT,
					error TEXT,
					attempts INTEGER NOT NULL DEFAULT 0,
					max_attempts INTEGER NOT NULL DEFAULT 3,
					failed_at INTEGER NOT NULL DEFAULT 0
				)";
				$sql[] = "CREATE INDEX IF NOT EXISTS idx_queue_jobs_queue_available ON `{$this->jobsTable}` (`queue`, `available_at`)";
				$sql[] = "CREATE INDEX IF NOT EXISTS idx_queue_jobs_reserved ON `{$this->jobsTable}` (`reserved_at`)";
				break;

			default: //mysql / mariadb
				$sql[] = "CREATE TABLE IF NOT EXISTS `{$this->jobsTable}` (
					`id` bigint unsigned NOT NULL AUTO_INCREMENT,
					`job_id` varchar(36) NOT NULL,
					`queue` varchar(128) NOT NULL DEFAULT 'default',
					`job` varchar(255) NOT NULL,
					`payload` json DEFAULT NULL,
					`attempts` int NOT NULL DEFAULT 0,
					`max_attempts` int NOT NULL DEFAULT 3,
					`available_at` bigint NOT NULL DEFAULT 0,
					`reserved_at` bigint DEFAULT NULL,
					`created_at` bigint NOT NULL DEFAULT 0,
					PRIMARY KEY (`id`),
					UNIQUE KEY `u_job_id` (`job_id`),
					KEY `idx_queue_available` (`queue`, `available_at`),
					KEY `idx_reserved_at` (`reserved_at`)
				) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
				$sql[] = "CREATE TABLE IF NOT EXISTS `{$this->failedTable}` (
					`job_id` varchar(36) NOT NULL,
					`queue` varchar(128) NOT NULL DEFAULT 'default',
					`job` varchar(255) NOT NULL,
					`payload` json DEFAULT NULL,
					`error` text,
					`attempts` int NOT NULL DEFAULT 0,
					`max_attempts` int NOT NULL DEFAULT 3,
					`failed_at` bigint NOT NULL DEFAULT 0,
					PRIMARY KEY (`job_id`),
					KEY `idx_failed_queue` (`queue`, `failed_at`)
				) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
				break;
		}

		foreach ($sql as $stmt) {
			try {
				$this->db->execute($stmt);
			} catch (\Throwable $e) {
				//duplicate index etc — ignore, tables exist from installer schema
			}
		}
	}

	/**
	 * @param string                  $queue Queue name.
	 * @param array<array-key, mixed> $job   Job record.
	 *
	 * @return string Job id.
	 */
	public function push(string $queue, array $job): string {
		$this->ensureSchema();

		$this->db->execute(
			"INSERT INTO {$this->jobsTable} (job_id, queue, job, payload, attempts, max_attempts, available_at, reserved_at, created_at)
			 VALUES (:id, :queue, :job, :payload, :attempts, :max_attempts, :available_at, :reserved_at, :created_at)",
			[
				'id'           => $job['id'],
				'queue'        => $queue,
				'job'          => $job['job'],
				'payload'      => json_encode($job['payload'] ?? []),
				'attempts'     => (int)($job['attempts'] ?? 0),
				'max_attempts' => (int)($job['max_attempts'] ?? 3),
				'available_at' => (int)($job['available_at'] ?? time()),
				'reserved_at'  => null,
				'created_at'   => (int)($job['created_at'] ?? time()),
			]
		);

		return (string)$job['id'];
	}

	/**
	 * Fetch a full job row by numeric id.
	 *
	 * @param int $numericId Numeric row id.
	 *
	 * @return array<array-key, mixed>|null
	 */
	protected function fetchRowById(int $numericId): ?array {
		$stmt = $this->db->execute(
			'SELECT ' . self::ROW_COLUMNS . " FROM {$this->jobsTable} WHERE id = :nid",
			['nid' => $numericId]
		);
		$rows = $this->db->fetchAll($stmt);

		return $rows ? $this->normalize($rows[0]) : null;
	}

	/**
	 * Atomically claim and return the next available job.
	 *
	 * @param string $queue Queue name.
	 *
	 * @return array<array-key, mixed>|null
	 */
	public function pop(string $queue): ?array {
		$this->ensureSchema();
		$now = time();

		switch ($this->driver) {
			case 'pgsql':
				//FOR UPDATE SKIP LOCKED lets multiple workers poll concurrently
				$stmt = $this->db->execute(
					"UPDATE {$this->jobsTable} SET reserved_at = :now, attempts = attempts + 1
					 WHERE id = (
						SELECT id FROM {$this->jobsTable}
						WHERE queue = :queue AND available_at <= :now AND reserved_at IS NULL
						ORDER BY available_at ASC
						FOR UPDATE SKIP LOCKED
						LIMIT 1
					 )
					 RETURNING " . self::ROW_COLUMNS,
					['now' => $now, 'queue' => $queue]
				);
				$rows = $this->db->fetchAll($stmt);

				return $rows ? $this->normalize($rows[0]) : null;

			case 'mysql':
				if ($this->supportsSkipLocked()) {
					//transactional SELECT ... FOR UPDATE SKIP LOCKED skips jobs locked by other workers
					$this->db->query('START TRANSACTION');

					try {
						$stmt = $this->db->execute(
							"SELECT id FROM {$this->jobsTable}
							 WHERE queue = :queue AND available_at <= :now AND reserved_at IS NULL
							 ORDER BY available_at ASC
							 LIMIT 1 FOR UPDATE SKIP LOCKED",
							['now' => $now, 'queue' => $queue]
						);
						$rows = $this->db->fetchAll($stmt);

						if (!$rows) {
							$this->db->query('COMMIT');

							return null;
						}

						$numericId = (int)$rows[0]['id'];

						$this->db->execute(
							"UPDATE {$this->jobsTable} SET reserved_at = :now, attempts = attempts + 1 WHERE id = :nid",
							['now' => $now, 'nid' => $numericId]
						);

						$this->db->query('COMMIT');

						return $this->fetchRowById($numericId);
					} catch (\Throwable $e) {
						try {
							$this->db->query('ROLLBACK');
						} catch (\Throwable $ignored) {
						}

						throw $e;
					}
				}

				//older servers: single-statement atomic claim, LAST_INSERT_ID(id) reads back the claimed row
				$this->db->execute(
					"UPDATE {$this->jobsTable} SET reserved_at = :now, attempts = attempts + 1, id = LAST_INSERT_ID(id)
					 WHERE queue = :queue AND available_at <= :now AND reserved_at IS NULL
					 ORDER BY available_at ASC
					 LIMIT 1",
					['now' => $now, 'queue' => $queue]
				);

				if (!$this->db->affected_rows || !$this->db->insert_id) {
					return null;
				}

				return $this->fetchRowById((int)$this->db->insert_id);

			case 'sqlite':
			default:
				//no row locks — serialize workers with an IMMEDIATE transaction
				$this->db->query('BEGIN IMMEDIATE');

				try {
					$stmt = $this->db->execute(
						"SELECT id FROM {$this->jobsTable}
						 WHERE queue = :queue AND available_at <= :now AND reserved_at IS NULL
						 ORDER BY available_at ASC
						 LIMIT 1",
						['now' => $now, 'queue' => $queue]
					);
					$rows = $this->db->fetchAll($stmt);

					if (!$rows) {
						$this->db->query('COMMIT');

						return null;
					}

					$numericId = (int)$rows[0]['id'];

					$this->db->execute(
						"UPDATE {$this->jobsTable} SET reserved_at = :now, attempts = attempts + 1 WHERE id = :nid",
						['now' => $now, 'nid' => $numericId]
					);

					$stmt = $this->db->execute(
						'SELECT ' . self::ROW_COLUMNS . " FROM {$this->jobsTable} WHERE id = :nid",
						['nid' => $numericId]
					);
					$rows = $this->db->fetchAll($stmt);

					$this->db->query('COMMIT');

					return $rows ? $this->normalize($rows[0]) : null;
				} catch (\Throwable $e) {
					try {
						$this->db->query('ROLLBACK');
					} catch (\Throwable $ignored) {
					}

					throw $e;
				}
		}
	}

	/**
	 * Remove a completed job.
	 *
	 * @param string $jobId Job id.
	 *
	 * @return void
	 */
	public function complete(string $jobId): void {
		$this->db->execute(
			"DELETE FROM {$this->jobsTable} WHERE job_id = :id",
			['id' => $jobId]
		);
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
		$this->db->execute(
			"UPDATE {$this->jobsTable} SET reserved_at = NULL, available_at = :available_at WHERE job_id = :id",
			['available_at' => time() + max(0, $delaySeconds), 'id' => $jobId]
		);
	}

	/**
	 * Handle failure: release with exponential backoff while retries remain,
	 * otherwise move to the failed store.
	 *
	 * @param string $jobId Job id.
	 * @param string $error Error message.
	 *
	 * @return bool True when moved to failed store, false when released for retry.
	 */
	public function fail(string $jobId, string $error): bool {
		$stmt = $this->db->execute(
			"SELECT job_id, queue, job, payload, attempts, max_attempts FROM {$this->jobsTable} WHERE job_id = :id",
			['id' => $jobId]
		);
		$rows = $this->db->fetchAll($stmt);

		if (!$rows) {
			return false; //already gone
		}

		$row = $this->normalize($rows[0]);

		if ($row['attempts'] < $row['max_attempts']) {
			$this->release($jobId, $this->backoffDelay($row['attempts']));

			return false;
		}

		//exhausted — move to failed store
		$this->db->execute(
			"INSERT INTO {$this->failedTable} (job_id, queue, job, payload, error, attempts, max_attempts, failed_at)
			 VALUES (:id, :queue, :job, :payload, :error, :attempts, :max_attempts, :failed_at)",
			[
				'id'           => $row['id'],
				'queue'        => $row['queue'],
				'job'          => $row['job'],
				'payload'      => json_encode($row['payload']),
				'error'        => mb_substr($error, 0, 65535),
				'attempts'     => $row['attempts'],
				'max_attempts' => $row['max_attempts'],
				'failed_at'    => time(),
			]
		);

		$this->complete($jobId);

		return true;
	}

	/**
	 * Pending (ready + delayed + reserved) job count on a queue.
	 *
	 * @param string $queue Queue name.
	 *
	 * @return int
	 */
	public function size(string $queue): int {
		$this->ensureSchema();

		$stmt = $this->db->execute(
			"SELECT COUNT(*) AS cnt FROM {$this->jobsTable} WHERE queue = :queue",
			['queue' => $queue]
		);
		$rows = $this->db->fetchAll($stmt);

		return (int)($rows[0]['cnt'] ?? 0);
	}

	/**
	 * Per-queue counters for monitoring.
	 *
	 * @param string $queue Queue name.
	 *
	 * @return array{ready: int, delayed: int, reserved: int}
	 */
	public function stats(string $queue): array {
		$this->ensureSchema();
		$now = time();

		$stmt = $this->db->execute(
			"SELECT
				SUM(CASE WHEN reserved_at IS NULL AND available_at <= :now THEN 1 ELSE 0 END) AS ready_cnt,
				SUM(CASE WHEN reserved_at IS NULL AND available_at > :now THEN 1 ELSE 0 END) AS delayed_cnt,
				SUM(CASE WHEN reserved_at IS NOT NULL THEN 1 ELSE 0 END) AS reserved_cnt
			 FROM {$this->jobsTable} WHERE queue = :queue",
			['now' => $now, 'queue' => $queue]
		);
		$rows = $this->db->fetchAll($stmt);
		$row  = $rows[0] ?? [];

		return [
			'ready'    => (int)($row['ready_cnt'] ?? 0),
			'delayed'  => (int)($row['delayed_cnt'] ?? 0),
			'reserved' => (int)($row['reserved_cnt'] ?? 0),
		];
	}

	/**
	 * Requeue jobs claimed by crashed workers.
	 *
	 * @param int $reservedTimeoutSeconds Age threshold in seconds.
	 *
	 * @return int Number of requeued jobs.
	 */
	public function requeueStalled(int $reservedTimeoutSeconds): int {
		$this->db->execute(
			"UPDATE {$this->jobsTable} SET reserved_at = NULL WHERE reserved_at IS NOT NULL AND reserved_at <= :cutoff",
			['cutoff' => time() - max(1, $reservedTimeoutSeconds)]
		);

		return (int)$this->db->affected_rows;
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
		$this->ensureSchema();

		$params = ['limit' => $limit];
		$where  = '';

		if ($queue) {
			$where           = 'WHERE queue = :queue';
			$params['queue'] = $queue;
		}

		$stmt = $this->db->execute(
			"SELECT job_id, queue, job, payload, error, attempts, max_attempts, failed_at
			 FROM {$this->failedTable} $where ORDER BY failed_at DESC LIMIT :limit",
			$params
		);
		$rows = $this->db->fetchAll($stmt);

		foreach ($rows as &$row) {
			$row['id']        = $row['job_id'];
			$row['payload']   = json_decode($row['payload'] ?? '[]', true);
			$row['attempts']  = (int)$row['attempts'];
			$row['failed_at'] = (int)$row['failed_at'];
		}

		return $rows;
	}

	/**
	 * Move a failed job back into the queue with attempts reset.
	 *
	 * @param string $jobId Job id.
	 *
	 * @return bool
	 */
	public function retryFailed(string $jobId): bool {
		$this->ensureSchema();

		$stmt = $this->db->execute(
			"SELECT job_id, queue, job, payload, attempts, max_attempts, failed_at FROM {$this->failedTable} WHERE job_id = :id",
			['id' => $jobId]
		);
		$rows = $this->db->fetchAll($stmt);

		if (!$rows) {
			return false;
		}

		$row = $rows[0];

		$this->push(
			$row['queue'],
			[
				'id'           => $row['job_id'],
				'queue'        => $row['queue'],
				'job'          => $row['job'],
				'payload'      => json_decode($row['payload'] ?? '[]', true),
				'attempts'     => 0,
				'max_attempts' => (int)$row['max_attempts'],
				'available_at' => time(),
				'created_at'   => (int)$row['failed_at'],
			]
		);

		return $this->deleteFailed($jobId);
	}

	/**
	 * Delete a single failed job.
	 *
	 * @param string $jobId Job id.
	 *
	 * @return bool
	 */
	public function deleteFailed(string $jobId): bool {
		$this->db->execute(
			"DELETE FROM {$this->failedTable} WHERE job_id = :id",
			['id' => $jobId]
		);

		return (int)$this->db->affected_rows > 0;
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
			$this->db->execute(
				"DELETE FROM {$this->failedTable} WHERE queue = :queue",
				['queue' => $queue]
			);
		} else {
			$this->db->execute("DELETE FROM {$this->failedTable}");
		}

		return (int)$this->db->affected_rows;
	}
}
