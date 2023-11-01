DROP TABLE IF EXISTS `field`;

CREATE TABLE `field` (
`field_id` INTEGER PRIMARY KEY AUTOINCREMENT,
`field_group_id` INT NOT NULL,
`type` TEXT NOT NULL,
`value` TEXT NOT NULL,
`status` INT NOT NULL DEFAULT '0',
`sort_order` INT NOT NULL DEFAULT '0'
-- PRIMARY KEY (`field_id`)
);

CREATE INDEX `field_field_group_id` ON `field` (`field_group_id`);

