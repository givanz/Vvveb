DROP TABLE IF EXISTS `option_content`;

CREATE TABLE `option_content` (
  `option_id` int(10) UNSIGNED NOT NULL,
  `language_id` int(10) UNSIGNED NOT NULL,
  `name` varchar(128) NOT NULL,
  PRIMARY KEY (`option_id`,`language_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
