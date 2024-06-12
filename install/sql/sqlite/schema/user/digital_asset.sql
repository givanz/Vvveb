DROP TABLE IF EXISTS `digital_asset`;

CREATE TABLE `digital_asset` (
`digital_asset_id` INTEGER PRIMARY KEY AUTOINCREMENT,
`file` TEXT NOT NULL,
`public` TEXT NOT NULL,
`created_at` datetime NOT NULL
-- PRIMARY KEY (`digital_asset_id`)
);
