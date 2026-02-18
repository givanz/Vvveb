DROP TABLE IF EXISTS `user_field_value`;

CREATE TABLE `user_field_value` (
`user_id` INTEGER NOT NULL,
`field_id` INTEGER NOT NULL,
`language_id` INT NOT NULL,
`value` TEXT NOT NULL
-- PRIMARY KEY (`field_value_id`)
);

CREATE INDEX `user_field_valuefield_id_language_id` ON `user_field_value` (`user_id`,`field_id`,`language_id`);
