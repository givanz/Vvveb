DROP TABLE IF EXISTS `product_field_value`;

CREATE TABLE `product_field_value` (
`product_field_value_id` INTEGER PRIMARY KEY AUTOINCREMENT,
`field_id` INTEGER NOT NULL,
`product_id` INTEGER NOT NULL,
`language_id` INT NOT NULL,
`value` TEXT NOT NULL
);

CREATE INDEX `product_field_valuefield_id_language_id` ON `product_field_value` (`product_id`,`field_id`,`language_id`);

CREATE INDEX IF NOT EXISTS `product_field_value_field` ON `product_field_value` (`field_id`);
