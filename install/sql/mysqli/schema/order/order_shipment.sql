DROP TABLE IF EXISTS `order_shipment`;

CREATE TABLE `order_shipment` (
  `order_shipment_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `order_id` INT UNSIGNED NOT NULL,
  `shipping_method` varchar(191) NOT NULL,
  `tracking_number` varchar(191) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`order_shipment_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4;