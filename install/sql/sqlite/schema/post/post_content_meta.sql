DROP TABLE IF EXISTS `post_content_meta`;

CREATE TABLE `post_content_meta` (
`post_id` INT NOT NULL DEFAULT '0',
`language_id` INT NOT NULL DEFAULT '0',
`namespace` TEXT DEFAULT NULL,
`key` TEXT DEFAULT NULL,
`value` TEXT,
PRIMARY KEY (`post_id`, `language_id`, `namespace`, `key`)
);
