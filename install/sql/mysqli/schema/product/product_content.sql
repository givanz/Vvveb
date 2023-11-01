DROP TABLE IF EXISTS `product_content`;

CREATE TABLE `product_content` (
  `product_id` INT UNSIGNED NOT NULL,
  `language_id` INT UNSIGNED NOT NULL,
  `name` varchar(191) NOT NULL DEFAULT "",
  `slug` varchar(191) NOT NULL DEFAULT "",
  `content` text,
  `tag` text,
  `meta_title` varchar(191) NOT NULL DEFAULT "",
  `meta_description` varchar(191) NOT NULL DEFAULT "",
  `meta_keywords` varchar(191) NOT NULL DEFAULT "",
  PRIMARY KEY (`product_id`,`language_id`),
  KEY `slug` (`slug`),
  FULLTEXT `search` (`name`,`content`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
