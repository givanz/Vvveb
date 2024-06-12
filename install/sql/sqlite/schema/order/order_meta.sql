DROP TABLE IF EXISTS `order_meta`;

CREATE TABLE `order_meta` (
`meta_id` INTEGER PRIMARY KEY AUTOINCREMENT,
`order_id` INT NOT NULL DEFAULT '0',
`key` TEXT DEFAULT NULL,
`value` TEXT
-- PRIMARY KEY (`meta_id`)
);

CREATE INDEX `order_meta_order_id` ON `order_meta` (`order_id`);
CREATE INDEX `order_meta_key` ON `order_meta` (`key`);
