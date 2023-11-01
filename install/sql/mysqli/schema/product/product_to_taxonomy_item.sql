DROP TABLE IF EXISTS `product_to_taxonomy_item`;

CREATE TABLE `product_to_taxonomy_item` (
  `product_id` INT UNSIGNED NOT NULL,
  `taxonomy_item_id` INT UNSIGNED NOT NULL,
  PRIMARY KEY (`product_id`,`taxonomy_item_id`),
  KEY `taxonomy_item_id` (`taxonomy_item_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4;