DROP TABLE IF EXISTS `taxonomy`;

CREATE TABLE `taxonomy` (
`taxonomy_id` INTEGER PRIMARY KEY AUTOINCREMENT,
`name` TEXT NOT NULL DEFAULT '',
`post_type` TEXT NOT NULL DEFAULT '',
`type` TEXT NOT NULL DEFAULT 'categories',
 `site_id` INT UNSIGNED NOT NULL DEFAULT 0
-- PRIMARY KEY (`taxonomy_id`)
);



CREATE INDEX `taxonomy_taxonomy_id` ON `taxonomy` (`taxonomy_id`);
