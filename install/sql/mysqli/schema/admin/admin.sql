DROP TABLE IF EXISTS `admin`;

CREATE TABLE `admin` (
  `admin_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `username` varchar(60) NOT NULL DEFAULT '',
  `first_name` varchar(32) NOT NULL DEFAULT '',
  `last_name` varchar(32) NOT NULL DEFAULT '',
  `password` varchar(191) NOT NULL DEFAULT '',
  `email` varchar(100) NOT NULL DEFAULT '',
  `phone_number` varchar(32) NOT NULL DEFAULT '',
  `url` varchar(100) NOT NULL DEFAULT '',
  `display_name` varchar(250) NOT NULL DEFAULT '',
  `role_id` INT UNSIGNED DEFAULT NULL,
  `status` INT UNSIGNED NOT NULL DEFAULT '0',
  `token` varchar(32) NOT NULL DEFAULT '',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`admin_id`),
  KEY `username` (`username`),
  KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4;
