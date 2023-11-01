DROP TABLE IF EXISTS `user_address`;

CREATE TABLE `user_address` (
  `user_address_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL,
  `first_name` varchar(32) NOT NULL,
  `last_name` varchar(32) NOT NULL,
  `company` varchar(60) NOT NULL,
  `address_1` varchar(128) NOT NULL,
  `address_2` varchar(128) NOT NULL,
  `country_id` INT UNSIGNED NOT NULL DEFAULT '0',
  `region_id` INT UNSIGNED NOT NULL DEFAULT '0',
  `city` varchar(128) NOT NULL,
  `post_code` varchar(10) NOT NULL,
  `default_address` tinyint unsigned NOT NULL DEFAULT 0,
  `fields` text,
  PRIMARY KEY (`user_address_id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4;
