DROP TABLE IF EXISTS `field`;

CREATE TABLE `field` (
`field_id` INTEGER PRIMARY KEY AUTOINCREMENT,
`field_group_id` INT NOT NULL,
`type` TEXT NOT NULL,
`default` TEXT NOT NULL,
`settings` TEXT NOT NULL,
`validation` TEXT NOT NULL,
`presentation` TEXT NOT NULL,
`conditionals` TEXT NOT NULL,
`row` INT NOT NULL DEFAULT 0,
`status` INT NOT NULL DEFAULT 1,
`sort_order` INT NOT NULL DEFAULT 0
-- PRIMARY KEY (`field_id`)
);

CREATE INDEX `field_field_group_id` ON `field` (`field_group_id`);

