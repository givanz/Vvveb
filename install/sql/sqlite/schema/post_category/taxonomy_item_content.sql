DROP TABLE IF EXISTS `taxonomy_item_content`;

CREATE TABLE `taxonomy_item_content` (
`taxonomy_item_id` INT NOT NULL,
`language_id` INT NOT NULL,
`name` TEXT NOT NULL,
`slug` TEXT NOT NULL DEFAULT '',
`content` text NOT NULL,
`meta_title` TEXT NOT NULL DEFAULT '',
`meta_description` TEXT NOT NULL DEFAULT '',
`meta_keywords` TEXT NOT NULL DEFAULT '',
PRIMARY KEY (`taxonomy_item_id`,`language_id`)
);

CREATE INDEX `taxonomy_item_content_name` ON `taxonomy_item_content` (`name`);
