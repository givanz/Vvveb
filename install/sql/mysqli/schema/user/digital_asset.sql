DROP TABLE IF EXISTS `digital_asset`;

CREATE TABLE `digital_asset` (
  `digital_asset_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `file` varchar(160) NOT NULL,
  `public` varchar(128) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`digital_asset_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4;
