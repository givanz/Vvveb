DROP TABLE IF EXISTS `taxonomy_item_field_value`;

CREATE TABLE `taxonomy_item_field_value` (
`taxonomy_item_field_value_id` INTEGER PRIMARY KEY AUTOINCREMENT,
`language_id` INT NOT NULL,
`taxonomy_item_id` INTEGER NOT NULL,
`field_id` INTEGER NOT NULL,
`sort_order` INTEGER NOT NULL DEFAULT 0
);

CREATE INDEX `taxonomy_item_field_value_field_id_language_id` ON `taxonomy_item_field_value` (`taxonomy_item_id`,`field_id`,`language_id`);

CREATE INDEX IF NOT EXISTS `taxonomy_item_field_value_field` ON `taxonomy_item_field_value` (`field_id`);
