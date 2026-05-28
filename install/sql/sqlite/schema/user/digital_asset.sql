DROP TABLE IF EXISTS `digital_asset`;

CREATE TABLE `digital_asset` (
`digital_asset_id` INTEGER PRIMARY KEY AUTOINCREMENT,
`admin_id` INT NOT NULL,
`file` TEXT NOT NULL,
`public` TEXT NOT NULL,
`created_at` datetime NOT NULL
-- PRIMARY KEY (`digital_asset_id`)
);

CREATE INDEX `digital_asset_admin` ON `post` (`admin_id`);