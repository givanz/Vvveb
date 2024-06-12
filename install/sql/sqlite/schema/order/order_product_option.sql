DROP TABLE IF EXISTS `order_product_option`;

CREATE TABLE `order_product_option` (
`order_product_option_id` INTEGER PRIMARY KEY AUTOINCREMENT,
`order_id` INT NOT NULL,
`order_product_id` INT NOT NULL,
`product_option_id` INT NOT NULL,
`product_option_value_id` INT NOT NULL DEFAULT '0',
`option` TEXT NOT NULL,
`name` TEXT NOT NULL,
`price` decimal(15,4) NOT NULL DEFAULT '0.0000',
`type` TEXT NOT NULL DEFAULT ''
-- PRIMARY KEY (`order_product_option_id`)
);