DROP TABLE IF EXISTS `digital_asset_log`;

CREATE TABLE `digital_asset_log` (
  `digital_asset_log_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `digital_asset_id` INT UNSIGNED NOT NULL,
  `user_id` INT UNSIGNED NOT NULL,
  `site_id` tinyint(6) NOT NULL,
  `ip` varchar(40) NOT NULL,
  `country` varchar(2),
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`digital_asset_log_id`),
  KEY (`user_id`),
  KEY (`digital_asset_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4;
