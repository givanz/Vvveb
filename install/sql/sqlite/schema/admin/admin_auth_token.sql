DROP TABLE IF EXISTS `admin_auth_token`;

CREATE TABLE `admin_auth_token` (
  `admin_auth_token_id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `admin_id` INT UNSIGNED NOT NULL,
  `token` TEXT NOT NULL,
  `description` TEXT NOT NULL DEFAULT '',
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX `admin_auth_token_token` ON `admin` (`token`,`admin_id`);