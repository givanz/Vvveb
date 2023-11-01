DROP TABLE IF EXISTS `order_status`;

CREATE TABLE `order_status` (
`order_status_id` INTEGER PRIMARY KEY AUTOINCREMENT,
`language_id` INT NOT NULL,
`name` TEXT NOT NULL
-- PRIMARY KEY (`order_status_id`,`language_id`)
);
