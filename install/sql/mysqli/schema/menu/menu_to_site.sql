DROP TABLE IF EXISTS `menu_to_site`;

CREATE TABLE `menu_to_site` (
  `menu_id` INT UNSIGNED NOT NULL,
  `site_id` tinyint(6) NOT NULL,
  PRIMARY KEY (`menu_id`,`site_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
