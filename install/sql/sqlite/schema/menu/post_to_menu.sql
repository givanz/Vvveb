DROP TABLE IF EXISTS `post_to_menu`;

CREATE TABLE `post_to_menu` (
`post_id` INT NOT NULL,
`menu_id` INT NOT NULL,
PRIMARY KEY (`post_id`,`menu_id`)
);

CREATE INDEX `post_to_menu_menu_id` ON `post_to_menu` (`menu_id`);
