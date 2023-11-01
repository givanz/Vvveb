DROP TABLE IF EXISTS `menu_item_content`;

CREATE TABLE `menu_item_content` (
  `menu_item_id` INT UNSIGNED NOT NULL,
  `language_id` INT UNSIGNED NOT NULL,
  `name` varchar(191) NOT NULL,
  `slug` varchar(191) NOT NULL DEFAULT '',
  `content` text NOT NULL,
  PRIMARY KEY (`menu_item_id`,`language_id`),
  KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
