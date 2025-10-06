DROP TABLE IF EXISTS `admin_auth_token`;

CREATE TABLE `admin_auth_token` (
  `admin_auth_token_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `admin_id` INT UNSIGNED NOT NULL,
  `token` varchar(191) NOT NULL,
  `description` varchar(191) NOT NULL DEFAULT '',
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`admin_auth_token_id`),
  KEY `token` (`token`, `admin_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4;
