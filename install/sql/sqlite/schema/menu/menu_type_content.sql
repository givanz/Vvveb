DROP TABLE IF EXISTS `menu_type_content`;

CREATE TABLE `menu_type_content` (
`menu_type_id` INT NOT NULL,
`language_id` INT NOT NULL,
`name` TEXT NOT NULL,
`slug` TEXT NOT NULL DEFAULT '',
`content` text NOT NULL,
PRIMARY KEY (`menu_type_id`,`language_id`)
);

CREATE INDEX `menu_type_content_name` ON `menu_type_content` (`name`);
