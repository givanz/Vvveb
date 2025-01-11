DROP TABLE IF EXISTS `user_failed_login`;

CREATE TABLE `user_failed_login` (
  `user_id` INT UNSIGNED NOT NULL,
  `count` INT UNSIGNED DEFAULT 0,
  `last_ip` TEXT NOT NULL DEFAULT '',
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`user_id`, `updated_at`)
);