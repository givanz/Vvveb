DROP TABLE IF EXISTS `field_group`;

CREATE TABLE `field_group` (
`field_group_id` INTEGER PRIMARY KEY AUTOINCREMENT,
`name` TEXT NOT NULL,
`status` INT NOT NULL  DEFAULT 0,
`sort_order` INT NOT NULL NOT NULL DEFAULT 0
--PRIMARY KEY (`field_group_id`)
);