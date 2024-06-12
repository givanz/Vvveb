DROP TABLE IF EXISTS `product_related`;

CREATE TABLE `product_related` (
`product_id` INT NOT NULL,
`product_related_id` INT NOT NULL,
PRIMARY KEY (`product_id`,`product_related_id`)
);
