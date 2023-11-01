DROP TABLE IF EXISTS `order_product`;

CREATE TABLE `order_product` (
`order_product_id` INTEGER PRIMARY KEY AUTOINCREMENT,
`order_id` INT NOT NULL,
`product_id` INT NOT NULL,
`name` TEXT NOT NULL,
`model` TEXT NOT NULL,
`quantity` INTEGER NOT NULL,
`price` decimal(15,4) NOT NULL DEFAULT '0.0000',
`total` decimal(15,4) NOT NULL DEFAULT '0.0000',
`tax` decimal(15,4) NOT NULL DEFAULT '0.0000',
`points` INTEGER NOT NULL
-- PRIMARY KEY (`order_product_id`)
);

CREATE INDEX `order_product_order_id` ON `order_product` (`order_id`);
