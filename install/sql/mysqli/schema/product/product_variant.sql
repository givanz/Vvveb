DROP TABLE IF EXISTS `product_variant`;

CREATE TABLE `product_variant` (
  `product_id` INT UNSIGNED NOT NULL,
  `product_variant_id` INT UNSIGNED NOT NULL,
  PRIMARY KEY (`product_id`,`product_variant_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4;
