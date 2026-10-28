DROP TABLE IF EXISTS `country`;

CREATE TABLE `country` (
`country_id` INTEGER PRIMARY KEY AUTOINCREMENT,
`name` TEXT NOT NULL,
`iso_code_2` TEXT NOT NULL,
`iso_code_3` VARCHAR(10) NOT NULL,
`status` TINYINT NOT NULL DEFAULT 0
-- PRIMARY KEY (`country_id`)
);





