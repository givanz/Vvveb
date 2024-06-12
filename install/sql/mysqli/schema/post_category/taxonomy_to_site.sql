DROP TABLE IF EXISTS `taxonomy_to_site`;

CREATE TABLE `taxonomy_to_site` (
  `taxonomy_item_id` INT UNSIGNED NOT NULL,
  `site_id` tinyint(6) NOT NULL,
  PRIMARY KEY (`taxonomy_item_id`,`site_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
