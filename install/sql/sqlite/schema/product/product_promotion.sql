DROP TABLE IF EXISTS `product_promotion`;

CREATE TABLE `product_promotion` (
`product_promotion_id` INTEGER PRIMARY KEY AUTOINCREMENT,
`product_id` INT NOT NULL,
`user_group_id` INT NOT NULL,
`priority` INTEGER NOT NULL DEFAULT '1',
`price` decimal(15,4) NOT NULL DEFAULT '0.0000',
`from_date` date NOT NULL DEFAULT '1000-01-01',
`to_date` date NOT NULL DEFAULT '1000-01-01'
-- PRIMARY KEY (`product_promotion_id`)
);

CREATE INDEX `product_promotion_product_id` ON `product_promotion` (`product_id`);
