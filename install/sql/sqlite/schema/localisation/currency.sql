DROP TABLE IF EXISTS `currency`;

CREATE TABLE `currency` (
`currency_id` INTEGER PRIMARY KEY AUTOINCREMENT,
`name` TEXT NOT NULL,
`code` TEXT NOT NULL,
`value` double(15,8) NOT NULL DEFAULT 1,
`sign_start` TEXT NOT NULL DEFAULT '',
`sign_end` TEXT NOT NULL DEFAULT '',
`decimal_place` INTEGER NOT NULL DEFAULT 2,
`status` TINYINT NOT NULL DEFAULT 0,
`updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
-- PRIMARY KEY (`currency_id`)
);
