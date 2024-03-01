DROP TABLE IF EXISTS `product_meta`;

CREATE TABLE `product_meta` (
`meta_id` INT,
`product_id` INT NOT NULL DEFAULT '0',
`namespace` TEXT DEFAULT NULL,
`key` TEXT DEFAULT NULL,
`value` TEXT,
PRIMARY KEY (`product_id`,`namespace`,`key`)
);
