DROP TABLE IF EXISTS `taxonomy_item_content`;

CREATE TABLE `taxonomy_item_content` (
  `taxonomy_item_id` INT UNSIGNED NOT NULL,
  `language_id` INT UNSIGNED NOT NULL,
  `name` varchar(191) NOT NULL,
  `slug` varchar(191) NOT NULL DEFAULT '',
  `content` text NOT NULL,
  `meta_title` varchar(191) NOT NULL DEFAULT '',
  `meta_description` varchar(191) NOT NULL DEFAULT '',
  `meta_keywords` varchar(191) NOT NULL DEFAULT '',
  PRIMARY KEY (`taxonomy_item_id`,`language_id`),
  KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

