DROP TABLE IF EXISTS `digital_asset_log`;

CREATE TABLE `digital_asset_log` (
`digital_asset_stats_id` INTEGER PRIMARY KEY AUTOINCREMENT,
`digital_asset_id` INT NOT NULL,
`user_id` INT NOT NULL,
`site_id` TINYINT NOT NULL,
`ip` TEXT NOT NULL,
`country` TEXT,
`created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
-- PRIMARY KEY (`digital_asset_stats_id`)
);

CREATE INDEX `digital_asset_log_user_id` ON `digital_asset_log` (`user_id`);
CREATE INDEX `digital_asset_log_digital_asset_id` ON `digital_asset_log` (`digital_asset_id`);