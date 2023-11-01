DROP TABLE IF EXISTS `product_attribute`;

CREATE TABLE `product_attribute` (
  `product_id` int(10) UNSIGNED NOT NULL,
  `attribute_id` int(10) UNSIGNED NOT NULL,
  `language_id` int(10) UNSIGNED NOT NULL,
  `value` text NOT NULL,
  PRIMARY KEY (`product_id`,`attribute_id`,`language_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
