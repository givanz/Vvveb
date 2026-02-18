DROP TABLE IF EXISTS `field_value_content`;

CREATE TABLE `field_value_content` (
  `field_value_id` int(10) UNSIGNED NOT NULL,
  `language_id` int(10) UNSIGNED NOT NULL,
  `field_id` int(10) UNSIGNED NOT NULL,
  `name` varchar(128) NOT NULL,
  `content` longtext NOT NULL,
  PRIMARY KEY (`field_value_id`,`language_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4;


DROP TABLE IF EXISTS `post_field_value_content`;

CREATE TABLE `post_field_value_content` (
  `post_field_value_id` int(10) UNSIGNED NOT NULL,
  `language_id` int(10) UNSIGNED NOT NULL,
  `field_id` int(10) UNSIGNED NOT NULL,
  `name` varchar(128) NOT NULL,
  `content` longtext NOT NULL,
  PRIMARY KEY (`post_field_value_id`,`language_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4;
