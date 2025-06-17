DROP TABLE IF EXISTS `post_content_meta`;

CREATE TABLE `post_content_meta` (
  `post_id` INT NOT NULL,
  `language_id` INT NOT NULL,
  `namespace` TEXT NOT NULL DEFAULT '',
  `key` TEXT NOT NULL,
  `value` TEXT
  -- PRIMARY KEY (`post_id`, `namespace`, `key`)
);

CREATE UNIQUE INDEX `post_content_meta_post_id` ON `post_content_meta` (`post_id`, `language_id`, `namespace`, `key`);
