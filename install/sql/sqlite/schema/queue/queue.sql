DROP TABLE IF EXISTS `queue_jobs`;

CREATE TABLE `queue_jobs` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `job_id` TEXT NOT NULL UNIQUE,
  `queue` TEXT NOT NULL DEFAULT 'default',
  `job` TEXT NOT NULL,
  `payload` TEXT,
  `attempts` INTEGER NOT NULL DEFAULT 0,
  `max_attempts` INTEGER NOT NULL DEFAULT 3,
  `available_at` INTEGER NOT NULL DEFAULT 0,
  `reserved_at` INTEGER DEFAULT NULL,
  `created_at` INTEGER NOT NULL DEFAULT 0
);

CREATE INDEX IF NOT EXISTS idx_queue_jobs_queue_available ON `queue_jobs` (`queue`, `available_at`);
CREATE INDEX IF NOT EXISTS idx_queue_jobs_reserved ON `queue_jobs` (`reserved_at`);

DROP TABLE IF EXISTS `failed_jobs`;

CREATE TABLE `failed_jobs` (
  `job_id` TEXT NOT NULL PRIMARY KEY,
  `queue` TEXT NOT NULL DEFAULT 'default',
  `job` TEXT NOT NULL,
  `payload` TEXT,
  `error` TEXT,
  `attempts` INTEGER NOT NULL DEFAULT 0,
  `max_attempts` INTEGER NOT NULL DEFAULT 3,
  `failed_at` INTEGER NOT NULL DEFAULT 0
);

CREATE INDEX IF NOT EXISTS idx_failed_jobs_queue_failed ON `failed_jobs` (`queue`, `failed_at`);
