DROP TABLE IF EXISTS `setting`;

CREATE TABLE `setting` (
-- `setting_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `site_id` tinyint(6) UNSIGNED NOT NULL DEFAULT 0,
  `namespace` varchar(128) NOT NULL,
  `key` varchar(128) NOT NULL,
  `value` text NOT NULL,
  PRIMARY KEY (`site_id`, `namespace`, `key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
