DROP TABLE IF EXISTS `product_meta`;

CREATE TABLE `product_meta` (
  `product_id` INT NOT NULL,
  `namespace` TEXT NOT NULL DEFAULT '',
  `key` TEXT NOT NULL,
  `value` TEXT
  -- PRIMARY KEY (`product_id`, `namespace`, `key`)
);

CREATE UNIQUE INDEX `product_meta_product_id` ON `product_meta` (`product_id`, `namespace`, `key`);
