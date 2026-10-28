DROP TABLE IF EXISTS `post_content`;

CREATE TABLE `post_content` (
  `post_id` INT UNSIGNED NOT NULL COMMENT 'Unique identifier for the post',
  `language_id` INT UNSIGNED NOT NULL COMMENT 'The id of the language of the content',
  `name` varchar(191) NOT NULL COMMENT 'The name for post language',
  `slug` varchar(191) NOT NULL COMMENT 'The slug for post language',
  `content` longtext NOT NULL COMMENT 'The content for post language',
  `excerpt` text COMMENT 'The excerpt for the post language',
  `meta_keywords` varchar(191) NOT NULL DEFAULT "" COMMENT 'The meta keyword for the post language',
  `meta_description` varchar(191) NOT NULL DEFAULT "" COMMENT 'The meta description for the post language',
  PRIMARY KEY (`post_id`,`language_id`),
  KEY `slug` (`slug`),
  FULLTEXT `search` (`name`,`content`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
