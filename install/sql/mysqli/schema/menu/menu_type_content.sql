DROP TABLE IF EXISTS `menu_type_content`;

CREATE TABLE `menu_type_content` (
  `menu_type_id` INT UNSIGNED NOT NULL,
  `language_id` INT UNSIGNED NOT NULL,
  `name` varchar(191) NOT NULL,
  `slug` varchar(191) NOT NULL DEFAULT '',
  `content` text NOT NULL,
  PRIMARY KEY (`menu_type_id`,`language_id`),
  KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
