DROP TABLE IF EXISTS `region`;

CREATE TABLE `region` (
`region_id` INTEGER PRIMARY KEY AUTOINCREMENT,
`country_id` INT NOT NULL,
`name` TEXT NOT NULL,
`code` TEXT NOT NULL,
`status` TINYINT NOT NULL DEFAULT '1'
-- PRIMARY KEY (`region_id`)
);
