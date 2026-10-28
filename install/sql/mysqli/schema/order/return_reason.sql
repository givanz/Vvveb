DROP TABLE IF EXISTS `return_reason`;

CREATE TABLE `return_reason` (
  `return_reason_id` INT UNSIGNED NOT NULL,
  `language_id` INT UNSIGNED NOT NULL DEFAULT '0',
  `name` varchar(128) NOT NULL,
  PRIMARY KEY (`return_reason_id`,`language_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;