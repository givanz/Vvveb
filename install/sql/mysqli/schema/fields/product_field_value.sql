DROP TABLE IF EXISTS `product_field_value`;

CREATE TABLE `product_field_value` (
  `product_field_value_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `field_id` int(10) UNSIGNED NOT NULL,
  `product_id` INT unsigned NOT NULL,
  `language_id` int(10) UNSIGNED NOT NULL,
  `value` text NOT NULL,
  PRIMARY KEY (`product_field_value_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4;
