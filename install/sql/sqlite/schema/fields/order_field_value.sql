DROP TABLE IF EXISTS `order_field_value`;

CREATE TABLE `order_field_value` (
`order_id` INTEGER NOT NULL,
`field_id` INTEGER NOT NULL,
`language_id` INT NOT NULL,
`value` TEXT NOT NULL
-- PRIMARY KEY (`field_value_id`)
);

CREATE INDEX `order_field_valuefield_id_language_id` ON `order_field_value` (`order_id`,`field_id`,`language_id`);
