DROP TABLE IF EXISTS `product_option_value`;

CREATE TABLE `product_option_value` (
  `product_option_value_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `product_option_id` INT UNSIGNED NOT NULL,
  `product_id` INT UNSIGNED NOT NULL,
  `option_id` INT UNSIGNED NOT NULL,
  `option_value_id` INT UNSIGNED NOT NULL,
  `quantity` int(3) NOT NULL,
  `subtract` tinyint NOT NULL DEFAULT 0,
  `price_operator` varchar(1) NOT NULL DEFAULT '+',
  `price` decimal(15,4) NOT NULL DEFAULT 0,
  `points_operator` varchar(1) NOT NULL DEFAULT '+',
  `points` int(8) NOT NULL DEFAULT 0,
  `weight_operator` varchar(1) NOT NULL DEFAULT '+',
  `weight` decimal(15,8) NOT NULL DEFAULT 0,
  PRIMARY KEY (`product_option_value_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4;
