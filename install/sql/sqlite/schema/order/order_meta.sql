DROP TABLE IF EXISTS `order_meta`;

CREATE TABLE `order_meta` (
  `order_id` INT NOT NULL,
  `namespace` TEXT NOT NULL DEFAULT '',
  `key` TEXT NOT NULL,
  `value` TEXT
  -- PRIMARY KEY (`meta_id`)
);

CREATE UNIQUE INDEX `order_meta_order_id` ON `order_meta` (`order_id`, `namespace`, `key`);
