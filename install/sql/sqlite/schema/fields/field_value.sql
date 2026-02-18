DROP TABLE IF EXISTS `field_value`;

CREATE TABLE `field_value` (
`field_id` INTEGER NOT NULL,
`value` TEXT NOT NULL,
`sort_order` INTEGER NOT NULL DEFAULT 0
-- PRIMARY KEY (`field_value_id`)
);

CREATE INDEX `field_value_field_id` ON `field_value` (`field_id`);
