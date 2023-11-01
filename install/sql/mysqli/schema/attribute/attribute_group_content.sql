DROP TABLE IF EXISTS `attribute_group_content`;

CREATE TABLE `attribute_group_content` (
  `attribute_group_id` int(10) UNSIGNED NOT NULL,
  `language_id` int(10) UNSIGNED NOT NULL,
  `name` varchar(64) NOT NULL,
  PRIMARY KEY (`attribute_group_id`,`language_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
