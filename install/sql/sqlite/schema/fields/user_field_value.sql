DROP TABLE IF EXISTS `user_field_value`;

CREATE TABLE `user_field_value` (
`user_field_value_id` INTEGER PRIMARY KEY AUTOINCREMENT,
`language_id` INT NOT NULL,
`user_id` INTEGER NOT NULL,
`field_id` INTEGER NOT NULL,
`sort_order` INTEGER NOT NULL DEFAULT 0
);

CREATE INDEX `user_field_value_field_id_language_id` ON `user_field_value` (`user_id`,`field_id`,`language_id`);

CREATE INDEX IF NOT EXISTS `user_field_value_field` ON `user_field_value` (`field_id`);
