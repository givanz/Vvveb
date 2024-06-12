DROP TABLE IF EXISTS `taxonomy_content`;

CREATE TABLE `taxonomy_content` (
  `taxonomy_id` INT UNSIGNED NOT NULL,
  `language_id` INT UNSIGNED NOT NULL,
  `name` varchar(191) NOT NULL,
  `slug` varchar(191) NOT NULL DEFAULT '',
  `content` text NOT NULL,
  PRIMARY KEY (`taxonomy_id`,`language_id`),
  KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
