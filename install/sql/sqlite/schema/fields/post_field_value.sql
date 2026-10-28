DROP TABLE IF EXISTS `post_field_value`;

CREATE TABLE `post_field_value` (
`post_id` INTEGER NOT NULL,
`field_id` INTEGER NOT NULL,
`language_id` INT NOT NULL,
`value` TEXT NOT NULL,
PRIMARY KEY (`post_id`,`field_id`,`language_id`)
);

CREATE INDEX IF NOT EXISTS `post_field_value_field` ON `post_field_value` (`field_id`);
