DROP TABLE IF EXISTS `product_to_site`;

CREATE TABLE `product_to_site` (
`product_id` INT NOT NULL,
`site_id` TINYINT NOT NULL DEFAULT '0',
PRIMARY KEY (`product_id`,`site_id`)
);
