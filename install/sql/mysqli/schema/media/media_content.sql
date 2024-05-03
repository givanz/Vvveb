DROP TABLE IF EXISTS `media_content`;

CREATE TABLE `media_content` (
  `media_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `language_id` INT UNSIGNED NOT NULL,
  `name` varchar(191) NOT NULL DEFAULT "",
--  `slug` varchar(191) NOT NULL DEFAULT "",
  `alt` varchar(191) NOT NULL DEFAULT "",
--  `content` longtext,
  PRIMARY KEY (`media_id`,`language_id`),
--  KEY `alt` (`alt`),
  FULLTEXT `search` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
