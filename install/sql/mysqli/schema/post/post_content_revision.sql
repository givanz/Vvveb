DROP TABLE IF EXISTS `post_content_revision`;

CREATE TABLE `post_content_revision` (
  `post_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `language_id` INT UNSIGNED NOT NULL,
  `content` longtext,
  `admin_id` INT UNSIGNED NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`post_id`,`language_id`, `created_at`, `admin_id`)
--  FULLTEXT `search` (`name`,`content`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
