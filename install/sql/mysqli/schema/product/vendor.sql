DROP TABLE IF EXISTS `vendor`;

CREATE TABLE `vendor` (
  `vendor_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(191) NOT NULL DEFAULT "",
  `slug` varchar(191) NOT NULL DEFAULT "",
  `image` varchar(191) NOT NULL,
  `sort_order` int(3) NOT NULL DEFAULT 0,
  PRIMARY KEY (`vendor_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4;

