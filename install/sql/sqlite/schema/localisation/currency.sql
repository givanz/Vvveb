DROP TABLE IF EXISTS `currency`;

CREATE TABLE `currency` (
`currency_id` INTEGER PRIMARY KEY AUTOINCREMENT,
`name` TEXT NOT NULL,
`code` TEXT NOT NULL,
`value` double(15,8) NOT NULL,
`sign_start` TEXT NOT NULL,
`sign_end` TEXT NOT NULL,
`decimal_place` char(1) NOT NULL,
`status` TINYINT NOT NULL,
`updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
-- PRIMARY KEY (`currency_id`)
);
