DROP TABLE IF EXISTS `field_value`;

CREATE TABLE `field_value` (
`field_value_id` INTEGER PRIMARY KEY AUTOINCREMENT,
`field_id` INTEGER NOT NULL,
`sort_order` INTEGER NOT NULL DEFAULT 0
);

CREATE INDEX `field_value_field_id` ON `field_value` (`field_id`);
