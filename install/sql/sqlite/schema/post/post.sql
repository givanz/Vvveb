DROP TABLE IF EXISTS `post`;

CREATE TABLE `post` (
`post_id` INTEGER PRIMARY KEY AUTOINCREMENT,
`admin_id` INT NOT NULL DEFAULT '0',
`status` TEXT NOT NULL DEFAULT 'publish',
`image` TEXT NOT NULL DEFAULT '',
`comment_status` TEXT NOT NULL DEFAULT 'open',
`password` TEXT NOT NULL DEFAULT '',
`parent` INT NOT NULL DEFAULT '0',
`sort_order` INT NOT NULL DEFAULT '0',
`type` TEXT NOT NULL DEFAULT 'post',
`template` TEXT NOT NULL DEFAULT '',
`comment_count` INT NOT NULL DEFAULT '0',
`created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
`updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
-- PRIMARY KEY (`post_id`)
);


CREATE INDEX `post_type_status_date` ON `post` (`type`);
CREATE INDEX `post_parent` ON `post` (`parent`);
CREATE INDEX `post_author` ON `post` (`admin_id`);
