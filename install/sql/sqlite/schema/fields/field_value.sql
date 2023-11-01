DROP TABLE IF EXISTS `field_value`;

CREATE TABLE `field_value` (
`field_id` INTEGER NOT NULL,
`language_id` INT NOT NULL,
`value` TEXT NOT NULL,
`sort_order` INTEGER NOT NULL
-- PRIMARY KEY (`field_value_id`)
);

CREATE INDEX `field_value_field_id_language_id` ON `field_value` (`field_id`,`language_id`);