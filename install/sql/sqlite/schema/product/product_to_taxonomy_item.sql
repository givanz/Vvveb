DROP TABLE IF EXISTS `product_to_taxonomy_item`;

CREATE TABLE `product_to_taxonomy_item` (
`product_id` INT NOT NULL,
`taxonomy_item_id` INT NOT NULL,
PRIMARY KEY (`product_id`,`taxonomy_item_id`)
);

CREATE INDEX `product_to_taxonomy_item_taxonomy_item_id` ON `product_to_taxonomy_item` (`taxonomy_item_id`);
