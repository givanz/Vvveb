DROP TABLE IF EXISTS `warehouse`;

CREATE TABLE `warehouse` (
  `warehouse_id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `name` TEXT NOT NULL DEFAULT '',
  `country_id` INT NOT NULL DEFAULT 0,
  `region_id` INT NOT NULL DEFAULT 0,
  `city` TEXT NOT NULL DEFAULT '',
  `postal_code` TEXT NOT NULL DEFAULT '',
  `address_1` TEXT NOT NULL DEFAULT '',
  `status` tinyint NOT NULL DEFAULT 1,
  `sort_order` int(3) NOT NULL DEFAULT 0
--  PRIMARY KEY (`warehouse_id`)
);
DROP TABLE IF EXISTS `product_warehouse`;

CREATE TABLE `product_warehouse` (
  `product_id` INT NOT NULL,
  `warehouse_id` INT NOT NULL,
  PRIMARY KEY (`product_id`,`warehouse_id`)
);
