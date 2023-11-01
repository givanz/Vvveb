DROP TABLE IF EXISTS `user_group_content`;

CREATE TABLE `user_group_content` (
  `user_group_id` INT UNSIGNED NOT NULL,
  `language_id` INT UNSIGNED NOT NULL,
  `name` varchar(32) NOT NULL,
  `content` text NOT NULL,
  PRIMARY KEY (`user_group_id`,`language_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4;
