DROP TABLE IF EXISTS `option_value_content`;

CREATE TABLE `option_value_content` (
  `option_value_id` int(10) UNSIGNED NOT NULL,
  `language_id` int(10) UNSIGNED NOT NULL,
  `option_id` int(10) UNSIGNED NOT NULL,
  `name` varchar(128) NOT NULL,
  PRIMARY KEY (`option_value_id`,`language_id`),
  KEY `option_id` (`option_id`, `language_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
