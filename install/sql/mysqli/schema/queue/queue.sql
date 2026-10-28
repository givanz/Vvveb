DROP TABLE IF EXISTS `queue_jobs`;

CREATE TABLE `queue_jobs` (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `failed_jobs`;

CREATE TABLE `failed_jobs` (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
