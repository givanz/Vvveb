DROP TABLE IF EXISTS `user_group_content`;

CREATE TABLE `user_group_content` (
`user_group_id` INT NOT NULL,
`language_id` INT NOT NULL,
`name` TEXT NOT NULL,
`content` text NOT NULL,
PRIMARY KEY (`user_group_id`,`language_id`)
);
