DROP TABLE IF EXISTS `order_total`;

CREATE TABLE `order_total` (
  `order_total_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `order_id` INT UNSIGNED NOT NULL,
  `key` varchar(32) NOT NULL DEFAULT '',
  `title` varchar(191) NOT NULL,
  `value` decimal(15,4) NOT NULL DEFAULT '0.0000',
  `sort_order` int(3) NOT NULL DEFAULT 0,
  PRIMARY KEY (`order_total_id`),
  KEY `order_id` (`order_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4;
