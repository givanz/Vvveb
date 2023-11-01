DROP TABLE IF EXISTS `tax_rate`;

CREATE TABLE `tax_rate` (
`tax_rate_id` INTEGER PRIMARY KEY AUTOINCREMENT,
`region_group_id` INT NOT NULL DEFAULT '0',
`name` TEXT NOT NULL,
`rate` decimal(15,4) NOT NULL DEFAULT '0.0000',
`type` char(1) NOT NULL,
`created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
`updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
-- PRIMARY KEY (`tax_rate_id`)
);
