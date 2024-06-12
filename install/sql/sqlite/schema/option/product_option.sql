DROP TABLE IF EXISTS `product_option`;

CREATE TABLE `product_option` (
  `product_option_id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `product_id` INT UNSIGNED NOT NULL,
  `option_id` INT UNSIGNED NOT NULL,
  `value` TEXT NOT NULL,
  `required` INT NOT NULL DEFAULT 0
--  PRIMARY KEY (`product_option_id`)
);
