DROP TABLE IF EXISTS `product_variant`;

CREATE TABLE `product_variant` (
  `product_variant_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `product_id` INT UNSIGNED NOT NULL,
  `options` varchar(191) NOT NULL DEFAULT '',
  `image` varchar(191) NOT NULL DEFAULT '',
  `price` decimal(15,4) NOT NULL DEFAULT 0.0000,
  `old_price` decimal(15,4) NOT NULL DEFAULT 0.0000,
  `stock_quantity` int(4) NOT NULL DEFAULT '0',
  `weight` decimal(15,8) NOT NULL DEFAULT '0.00000000',
  `sku` varchar(64) NOT NULL DEFAULT '',
  `barcode` varchar(64) NOT NULL DEFAULT '',
  PRIMARY KEY (`product_variant_id`),
  KEY (`product_id`, `stock_quantity`, `options`),
  KEY (`options`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4;
