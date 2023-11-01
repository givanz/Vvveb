DROP TABLE IF EXISTS `comment`;

CREATE TABLE `comment` (
`comment_id` INTEGER  NOT NULL ,
`post_id` INTEGER  NOT NULL DEFAULT '0',
`user_id` INTEGER  NOT NULL DEFAULT '0',
`author` tinytext NOT NULL,
`email` TEXT  NOT NULL DEFAULT '',
`url` TEXT  NOT NULL DEFAULT '',
`ip` TEXT  NOT NULL DEFAULT '',
`content` text  NOT NULL,
`status` TINYINT NOT NULL DEFAULT 0,
`votes` SMALLINTEGER  NOT NULL DEFAULT 0,
`type` TEXT  NOT NULL DEFAULT '',
`parent_id` INT NOT NULL DEFAULT '0',
`created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
`updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
PRIMARY KEY (`comment_id`)
);


CREATE INDEX `comment_post_id` ON `comment` (`post_id`);
CREATE INDEX `comment_parent` ON `comment` (`parent_id`);
CREATE INDEX `comment_email` ON `comment` (`email`);

