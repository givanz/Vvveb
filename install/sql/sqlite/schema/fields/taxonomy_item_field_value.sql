DROP TABLE IF EXISTS `taxonomy_item_field_value`;

CREATE TABLE `taxonomy_item_field_value` (
`taxonomy_item_id` INTEGER NOT NULL,
`field_id` INTEGER NOT NULL,
`language_id` INT NOT NULL,
`value` TEXT NOT NULL
-- PRIMARY KEY (`field_value_id`)
);

CREATE INDEX `taxonomy_item_field_valuefield_id_language_id` ON `taxonomy_item_field_value` (`taxonomy_item_id`,`field_id`,`language_id`);
