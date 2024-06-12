DROP TABLE IF EXISTS `product_points`;

CREATE TABLE `product_points` (
`product_points_id` INTEGER PRIMARY KEY AUTOINCREMENT,
`product_id` INT NOT NULL DEFAULT '0',
`user_group_id` INT NOT NULL DEFAULT '0',
`points` INTEGER NOT NULL DEFAULT '0'
-- PRIMARY KEY (`product_points_id`)
);
