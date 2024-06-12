DROP TABLE IF EXISTS `product_discount`;

CREATE TABLE `product_discount` (
`product_discount_id` INTEGER PRIMARY KEY AUTOINCREMENT,
`product_id` INT NOT NULL,
`user_group_id` INT NOT NULL,
`quantity` INTEGER NOT NULL DEFAULT '0',
`priority` INTEGER NOT NULL DEFAULT '1',
`price` decimal(15,4) NOT NULL DEFAULT '0.0000',
`from_date` date NOT NULL DEFAULT '1000-01-01',
`to_date` date NOT NULL DEFAULT '1000-01-01'
-- PRIMARY KEY (`product_discount_id`)
);

CREATE INDEX `product_discount_product_id` ON `product_discount` (`product_id`);
