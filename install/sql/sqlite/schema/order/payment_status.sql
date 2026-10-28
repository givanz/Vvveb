DROP TABLE IF EXISTS `payment_status`;

CREATE TABLE `payment_status` (
`payment_status_id` INTEGER PRIMARY KEY AUTOINCREMENT,
`language_id` INT NOT NULL,
`name` TEXT NOT NULL
-- PRIMARY KEY (`payment_status_id`,`language_id`)
);
