DROP TABLE IF EXISTS `country`;

CREATE TABLE `country` (
`country_id` INTEGER PRIMARY KEY AUTOINCREMENT,
`name` TEXT NOT NULL,
`iso_code_2` TEXT NOT NULL,
`iso_code_3` TEXT NOT NULL,
`status` TINYINT NOT NULL DEFAULT '1'
-- PRIMARY KEY (`country_id`)
);





