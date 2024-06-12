DROP TABLE IF EXISTS `order_total`;

CREATE TABLE `order_total` (
`order_total_id`INTEGER PRIMARY KEY AUTOINCREMENT,
`order_id` INT NOT NULL,
`key` TEXT NOT NULL DEFAULT '',
`title` TEXT NOT NULL,
`value` decimal(15,4) NOT NULL DEFAULT '0.0000',
`sort_order` INTEGER NOT NULL DEFAULT 0
-- PRIMARY KEY (`order_total_id`)
);

CREATE INDEX `order_total_order_id` ON `order_total` (`order_id`);
