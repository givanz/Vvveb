DROP TABLE IF EXISTS `post_to_site`;

CREATE TABLE `post_to_site` (
`post_id` INT NOT NULL,
`site_id` TINYINT NOT NULL DEFAULT '0',
PRIMARY KEY (`post_id`,`site_id`)
);

CREATE INDEX IF NOT EXISTS `post_to_site_site` ON `post_to_site` (`site_id`);
