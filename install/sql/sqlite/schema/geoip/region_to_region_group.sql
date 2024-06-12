DROP TABLE IF EXISTS `region_to_region_group`;

CREATE TABLE `region_to_region_group` (
  `region_to_region_group_id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `country_id` INT UNSIGNED NOT NULL,
  `region_id` INT UNSIGNED NOT NULL DEFAULT '0',
  `region_group_id` INT UNSIGNED NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
--  PRIMARY KEY (`region_to_region_group_id`)
);