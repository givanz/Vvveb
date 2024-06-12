DROP TABLE IF EXISTS `order_product_option`;

CREATE TABLE `order_product_option` (
  `order_product_option_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `order_id` INT UNSIGNED NOT NULL,
  `order_product_id` INT UNSIGNED NOT NULL,
  `product_option_id` INT UNSIGNED NOT NULL,
  `product_option_value_id` INT UNSIGNED NOT NULL DEFAULT '0',
  `option` varchar(191) NOT NULL,
  `name` varchar(191) NOT NULL,
  `price` decimal(15,4) NOT NULL DEFAULT '0.0000',
  `type` varchar(32) NOT NULL DEFAULT '',
  PRIMARY KEY (`order_product_option_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4;