DROP TABLE IF EXISTS `product_variant`;

CREATE TABLE `product_variant` (
`product_id` INT NOT NULL,
`product_variant_id` INT NOT NULL,
PRIMARY KEY (`product_id`,`product_variant_id`)
);
