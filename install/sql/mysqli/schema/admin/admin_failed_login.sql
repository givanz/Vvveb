DROP TABLE IF EXISTS `admin_failed_login`;

CREATE TABLE `admin_failed_login` (
  `admin_id` INT UNSIGNED NOT NULL,
  `count` INT UNSIGNED DEFAULT 0,
  `last_ip` VARCHAR(16) NOT NULL DEFAULT '',
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`admin_id`, `updated_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
