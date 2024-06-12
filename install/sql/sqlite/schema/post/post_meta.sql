DROP TABLE IF EXISTS `post_meta`;

CREATE TABLE `post_meta` (
`meta_id` INT,
`post_id` INT NOT NULL DEFAULT '0',
`namespace` TEXT DEFAULT NULL,
`key` TEXT DEFAULT NULL,
`value` TEXT,
PRIMARY KEY (`post_id`,`namespace`,`key`)
);
