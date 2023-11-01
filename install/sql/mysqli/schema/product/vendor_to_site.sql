DROP TABLE IF EXISTS `vendor_to_site`;

CREATE TABLE `vendor_to_site` (
  `vendor_id` INT UNSIGNED NOT NULL,
  `site_id` tinyint(6) NOT NULL DEFAULT '0',
  PRIMARY KEY (`vendor_id`,`site_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4;

