DROP TABLE IF EXISTS `return_status`;

CREATE TABLE `return_status` (
  `return_status_id` INT UNSIGNED NOT NULL,
  `language_id` INT UNSIGNED NOT NULL DEFAULT '0',
  `name` varchar(32) NOT NULL,
  PRIMARY KEY (`return_status_id`,`language_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;