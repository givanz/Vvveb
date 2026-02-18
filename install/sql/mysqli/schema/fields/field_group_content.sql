DROP TABLE IF EXISTS `field_group_content`;

CREATE TABLE `field_group_content` (
  `field_group_id` int NOT NULL,
  `language_id` int NOT NULL,
  `name` varchar(128) NOT NULL,
  `slug` varchar(191) NOT NULL COMMENT 'The slug for post language',
  `content` longtext NOT NULL COMMENT 'The content for post language',
  PRIMARY KEY (`field_group_id`,`language_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4;
