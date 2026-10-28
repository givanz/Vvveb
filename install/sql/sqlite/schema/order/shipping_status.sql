DROP TABLE IF EXISTS `shipping_status`;

CREATE TABLE `shipping_status` (
`shipping_status_id` INTEGER PRIMARY KEY AUTOINCREMENT,
`language_id` INT NOT NULL,
`name` TEXT NOT NULL
-- PRIMARY KEY (`shipping_status_id`,`language_id`)
);
