DROP TABLE IF EXISTS `product_to_site`;

CREATE TABLE `product_to_site` (
  `product_id` INT UNSIGNED NOT NULL,
  `site_id` tinyint(6) NOT NULL DEFAULT '0',
  PRIMARY KEY (`product_id`,`site_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
