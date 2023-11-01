DROP TABLE IF EXISTS `product_option_value`;

CREATE TABLE `product_option_value` (
`product_option_value_id` INTEGER PRIMARY KEY AUTOINCREMENT,
`product_option_id` INT NOT NULL,
`product_id` INT NOT NULL,
`option_id` INT NOT NULL,
`option_value_id` INT NOT NULL,
`quantity` INT NOT NULL DEFAULT 0,
`subtract` TINYINT NOT NULL DEFAULT 0,
`price_operator` TEXT NOT NULL DEFAULT '+',
`price` decimal(15,4) NOT NULL  DEFAULT 0,
`points_operator` TEXT NOT NULL DEFAULT '+',
`points` INT NOT NULL DEFAULT 0,
`weight_operator` TEXT NOT NULL DEFAULT '+',
`weight` decimal(15,8) NOT NULL DEFAULT 0
-- PRIMARY KEY (`product_option_value_id`)
);
