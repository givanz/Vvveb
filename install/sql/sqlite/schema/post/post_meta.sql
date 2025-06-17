DROP TABLE IF EXISTS `post_meta`;

CREATE TABLE `post_meta` (
  `post_id` INT NOT NULL,
  `namespace` TEXT NOT NULL DEFAULT '',
  `key` TEXT NOT NULL,
  `value` TEXT
  -- PRIMARY KEY (`post_id`, `namespace`, `key`)
);

CREATE UNIQUE INDEX `post_meta_post_id` ON `post_meta` (`post_id`, `namespace`, `key`);
