DROP TABLE IF EXISTS `menu_item_content`;

CREATE TABLE `menu_item_content` (
`menu_item_id` INT NOT NULL,
`language_id` INT NOT NULL,
`name` TEXT NOT NULL,
`slug` TEXT NOT NULL DEFAULT '',
`content` text NOT NULL,
PRIMARY KEY (`menu_item_id`,`language_id`)
);

CREATE INDEX `menu_item_content_name` ON `menu_item_content` (`name`);
