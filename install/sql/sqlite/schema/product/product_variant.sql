DROP TABLE IF EXISTS `product_variant`;

CREATE TABLE `product_variant` (
  `product_variant_id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `product_id` INT UNSIGNED NOT NULL,
  `options`TEXT NOT NULL DEFAULT '',
  `image` TEXT NOT NULL DEFAULT '',
  `price` decimal(15,4) NOT NULL DEFAULT 0.0000,
  `old_price` decimal(15,4) NOT NULL DEFAULT 0.0000,
  `stock_quantity` int(4) NOT NULL DEFAULT '0',
  `weight` decimal(15,8) NOT NULL DEFAULT '0.00000000',
  `sku` varchar(64) NOT NULL DEFAULT '',
  `barcode` varchar(64) NOT NULL DEFAULT ''
--  PRIMARY KEY (`product_variant_id`),
--  KEY (`product_id`,`product_variant_id`, `product_option`)
);

CREATE INDEX `product_variant_product_id_product_option` ON `product_variant` (`product_id`,`product_variant_id`, `options`);