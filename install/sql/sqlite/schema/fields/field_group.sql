DROP TABLE IF EXISTS `field_group`;

CREATE TABLE `field_group` (
`field_group_id` INTEGER PRIMARY KEY AUTOINCREMENT,
`type` TEXT NOT NULL DEFAULT 'post',
`status` INT NOT NULL  DEFAULT 0,
`sort_order` INT NOT NULL NOT NULL DEFAULT 0
--PRIMARY KEY (`field_group_id`)
);