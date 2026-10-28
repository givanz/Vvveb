DROP TABLE IF EXISTS `product_option`;

CREATE TABLE `product_option` (
  `product_option_id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `product_id` INT UNSIGNED NOT NULL,
  `option_id` INT UNSIGNED NOT NULL,
  `value` TEXT NOT NULL,
  `sort_order` INT NOT NULL DEFAULT 0,
  `required` INT NOT NULL DEFAULT 0
--  PRIMARY KEY (`product_option_id`)
);

CREATE INDEX `product_option_product_id` ON `product_option` (`product_id`);
CREATE INDEX `product_option_option_id` ON `product_option` (`option_id`);
