DROP TABLE IF EXISTS `weight_type_content`;

CREATE TABLE `weight_type_content` (
  `weight_type_id` INT UNSIGNED NOT NULL,
  `language_id` INT UNSIGNED NOT NULL,
  `name` varchar(32) NOT NULL,
  `unit` varchar(4) NOT NULL,
  PRIMARY KEY (`weight_type_id`,`language_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;