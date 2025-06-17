DROP TABLE IF EXISTS `product_content_meta`;

CREATE TABLE `product_content_meta` (
  `product_id` INT NOT NULL,
  `language_id` INT NOT NULL,
  `namespace` TEXT NOT NULL DEFAULT '',
  `key` TEXT NOT NULL,
  `value` TEXT
  -- PRIMARY KEY (`product_id`, `namespace`, `key`)
);

CREATE UNIQUE INDEX `product_content_meta_product_id` ON `product_content_meta` (`product_id`, `language_id`, `namespace`, `key`);
