DROP TABLE IF EXISTS `digital_asset_content`;

CREATE TABLE `digital_asset_content` (
  `digital_asset_id` INT UNSIGNED NOT NULL,
  `language_id` INT UNSIGNED NOT NULL,
  `name` varchar(64) NOT NULL,
  PRIMARY KEY (`digital_asset_id`,`language_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4;
