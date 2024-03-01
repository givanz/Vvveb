DROP TABLE IF EXISTS `product_content_meta`;

CREATE TABLE `product_content_meta` (
`product_id` INT NOT NULL DEFAULT '0',
`language_id` INT NOT NULL DEFAULT '0',
`namespace` TEXT DEFAULT NULL,
`key` TEXT DEFAULT NULL,
`value` TEXT,
PRIMARY KEY (`product_id`, `language_id`, `namespace`, `key`)
);
