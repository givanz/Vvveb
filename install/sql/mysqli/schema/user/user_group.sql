DROP TABLE IF EXISTS `user_group`;

CREATE TABLE `user_group` (
  `user_group_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `status` tinyint NOT NULL,
  `sort_order` int NOT NULL DEFAULT 0,
  PRIMARY KEY (`user_group_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4;