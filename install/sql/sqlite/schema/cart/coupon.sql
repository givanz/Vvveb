DROP TABLE IF EXISTS `coupon`;

CREATE TABLE `coupon` (
`coupon_id` INTEGER PRIMARY KEY AUTOINCREMENT,
`name` TEXT NOT NULL,
`code` TEXT NOT NULL,
`type` char(1) NOT NULL,
`discount` decimal(15,4) NOT NULL,
`total` decimal(15,4) NOT NULL,
`limit` INT NOT NULL,
`limit_user` TEXT NOT NULL,
`logged_in` TINYINT NOT NULL,
`free_shipping` TINYINT NOT NULL,
`status` TINYINT NOT NULL,
`from_date` date NOT NULL DEFAULT '1000-01-01',
`to_date` date NOT NULL DEFAULT '1000-01-01',
`created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
`updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
-- PRIMARY KEY (`coupon_id`)
);
