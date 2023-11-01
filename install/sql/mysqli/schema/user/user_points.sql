DROP TABLE IF EXISTS `user_points`;

CREATE TABLE `user_points` (
  `user_points_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL DEFAULT '0',
  `order_id` INT UNSIGNED NOT NULL DEFAULT '0',
  `content` text NOT NULL,
  `points` int(8) NOT NULL DEFAULT '0',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`user_points_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4;