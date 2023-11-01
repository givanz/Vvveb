DROP TABLE IF EXISTS `product_points`;

CREATE TABLE `product_points` (
  `product_points_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `product_id` INT UNSIGNED NOT NULL DEFAULT '0',
  `user_group_id` INT UNSIGNED NOT NULL DEFAULT '0',
  `points` int(8) NOT NULL DEFAULT '0',
  PRIMARY KEY (`product_points_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4;