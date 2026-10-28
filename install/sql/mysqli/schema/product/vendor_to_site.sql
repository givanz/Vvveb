DROP TABLE IF EXISTS `vendor_to_site`;

CREATE TABLE `vendor_to_site` (
  `vendor_id` INT UNSIGNED NOT NULL,
  `site_id` tinyint(6) NOT NULL DEFAULT '0',
  PRIMARY KEY (`vendor_id`,`site_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE INDEX `vendor_to_site_site` ON `vendor_to_site` (`site_id`);
