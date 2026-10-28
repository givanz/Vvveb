DROP TABLE IF EXISTS `warehouse`;

CREATE TABLE `warehouse` (
  `warehouse_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(191) NOT NULL DEFAULT '',
  `country_id` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `region_id` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `city` varchar(191) NOT NULL DEFAULT '',
  `postal_code` varchar(32) NOT NULL DEFAULT '',
  `address_1` varchar(191) NOT NULL DEFAULT '',
  `status` tinyint NOT NULL DEFAULT 1,
  `sort_order` int(3) NOT NULL DEFAULT 0,
  PRIMARY KEY (`warehouse_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4;
DROP TABLE IF EXISTS `product_warehouse`;

CREATE TABLE `product_warehouse` (
  `product_id` int(10) UNSIGNED NOT NULL,
  `warehouse_id` int(10) UNSIGNED NOT NULL,
  PRIMARY KEY (`product_id`,`warehouse_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
