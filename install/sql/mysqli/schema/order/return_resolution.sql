DROP TABLE IF EXISTS `return_resolution`;

CREATE TABLE `return_resolution` (
  `return_resolution_id` INT UNSIGNED NOT NULL,
  `language_id` INT UNSIGNED NOT NULL DEFAULT '0',
  `name` varchar(64) NOT NULL,
  PRIMARY KEY (`return_resolution_id`,`language_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;