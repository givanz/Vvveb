DROP TABLE IF EXISTS `order_field_value`;

CREATE TABLE `order_field_value` (
`order_field_value_id` INTEGER PRIMARY KEY AUTOINCREMENT,
`language_id` INT NOT NULL,
`order_id` INTEGER NOT NULL,
`field_id` INTEGER NOT NULL,
`sort_order` INTEGER NOT NULL DEFAULT 0
);

CREATE INDEX `order_field_value_field_id_language_id` ON `order_field_value` (`order_id`,`field_id`,`language_id`);

CREATE INDEX IF NOT EXISTS `order_field_value_field` ON `order_field_value` (`field_id`);
