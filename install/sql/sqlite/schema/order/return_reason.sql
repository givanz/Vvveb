DROP TABLE IF EXISTS `return_reason`;

CREATE TABLE `return_reason` (
`return_reason_id` INTEGER PRIMARY KEY AUTOINCREMENT,
`language_id` INT NOT NULL DEFAULT '0',
`name` TEXT NOT NULL
-- PRIMARY KEY (`return_reason_id`,`language_id`)
);
