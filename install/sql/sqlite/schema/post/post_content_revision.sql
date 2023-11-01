DROP TABLE IF EXISTS `post_content_revision`;

CREATE TABLE `post_content_revision` (
  `post_id`  INT UNSIGNED NOT NULL,
  `language_id` INT UNSIGNED NOT NULL,
  `content` TEXT,
  `admin_id` INT UNSIGNED NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
--  PRIMARY KEY (`post_id`,`language_id`, `created_at`)
--  FULLTEXT `search` (`name`,`content`)
);

CREATE INDEX `post_content_revision_primary` ON `post_content_revision` (`post_id`,`language_id`, `created_at`,`admin_id`);