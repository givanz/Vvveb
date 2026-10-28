DROP TABLE IF EXISTS `manufacturer_to_site`;

CREATE TABLE `manufacturer_to_site` (
  `manufacturer_id` INT UNSIGNED NOT NULL,
  `site_id` tinyint(6) NOT NULL DEFAULT '0',
  PRIMARY KEY (`manufacturer_id`,`site_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE INDEX `manufacturer_to_site_site` ON `manufacturer_to_site` (`site_id`);
