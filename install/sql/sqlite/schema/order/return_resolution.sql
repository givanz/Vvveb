DROP TABLE IF EXISTS `return_resolution`;

CREATE TABLE `return_resolution` (
`return_resolution_id`  INTEGER PRIMARY KEY AUTOINCREMENT,
`language_id` INT NOT NULL DEFAULT '0',
`name` TEXT NOT NULL
-- PRIMARY KEY (`return_resolution_id`,`language_id`)
);
