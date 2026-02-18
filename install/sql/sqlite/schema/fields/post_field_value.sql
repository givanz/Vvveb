DROP TABLE IF EXISTS `post_field_value`;

CREATE TABLE `post_field_value` (
`post_id` INTEGER NOT NULL,
`field_id` INTEGER NOT NULL,
`language_id` INT NOT NULL,
`value` TEXT NOT NULL
-- PRIMARY KEY (`field_value_id`)
);

CREATE INDEX `post_field_valuefield_id_language_id` ON `post_field_value` (`post_id`,`field_id`,`language_id`);
