DROP TABLE IF EXISTS `product_to_digital_asset`;

CREATE TABLE `product_to_digital_asset` (
  `product_id` INT UNSIGNED NOT NULL,
  `digital_asset_id` INT UNSIGNED NOT NULL,
  PRIMARY KEY (`product_id`,`digital_asset_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4;
