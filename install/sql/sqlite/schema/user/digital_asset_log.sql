DROP TABLE IF EXISTS `digital_asset_log`;

CREATE TABLE `digital_asset_log` (
`digital_asset_stats_id` INTEGER PRIMARY KEY AUTOINCREMENT,
`digital_asset_id` INT NOT NULL,
`site_id` TINYINT NOT NULL,
`ip` TEXT NOT NULL,
`country` TEXT NOT NULL,
`created_at` datetime NOT NULL
-- PRIMARY KEY (`digital_asset_stats_id`)
);
