DROP TABLE IF EXISTS `product_related`;

CREATE TABLE `product_related` (
  `product_id` INT UNSIGNED NOT NULL,
  `product_related_id` INT UNSIGNED NOT NULL,
  PRIMARY KEY (`product_id`,`product_related_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE INDEX `product_related_related` ON `product_related` (`product_related_id`);
