DROP TABLE IF EXISTS `return_status`;

CREATE TABLE `return_status` (
`return_status_id` INTEGER PRIMARY KEY AUTOINCREMENT,
`language_id` INT NOT NULL DEFAULT '0',
`name` TEXT NOT NULL
-- PRIMARY KEY (`return_status_id`,`language_id`)
);
