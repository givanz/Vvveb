DROP TABLE IF EXISTS `product_option`;

CREATE TABLE `product_option` (
  `product_option_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `product_id` INT UNSIGNED NOT NULL,
  `option_id` INT UNSIGNED NOT NULL,
  `value` text NOT NULL,
  `required` tinyint NOT NULL DEFAULT 0,
  PRIMARY KEY (`product_option_id`),
  KEY `product_option` (`product_id`),
  KEY `option_id` (`option_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4;