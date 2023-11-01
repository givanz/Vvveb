DROP TABLE IF EXISTS `taxonomy_content`;

CREATE TABLE `taxonomy_content` (
`taxonomy_id` INT NOT NULL,
`language_id` INT NOT NULL,
`name` TEXT NOT NULL,
`slug` TEXT NOT NULL DEFAULT '',
`content` text NOT NULL,
PRIMARY KEY (`taxonomy_id`,`language_id`)
);

CREATE INDEX `taxonomy_content_name` ON `taxonomy_content` (`name`);
