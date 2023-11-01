DROP TABLE IF EXISTS `order_product_option`;

CREATE TABLE `order_product_option` (
`order_product_option_id` INTEGER PRIMARY KEY AUTOINCREMENT,
`order_id` INT NOT NULL,
`order_product_id` INT NOT NULL,
`product_option_id` INT NOT NULL,
`product_option_value_id` INT NOT NULL DEFAULT '0',
`name` TEXT NOT NULL,
`value` text NOT NULL,
`type` TEXT NOT NULL
-- PRIMARY KEY (`order_product_option_id`)
);