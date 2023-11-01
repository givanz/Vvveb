DROP TABLE IF EXISTS `order_log`;

CREATE TABLE `order_log` (
`order_log_id` INTEGER PRIMARY KEY AUTOINCREMENT,
`order_id` INT NOT NULL,
`order_status_id` INT NOT NULL,
`notify` TINYINT NOT NULL DEFAULT '0',
`note` text NOT NULL,
`created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
-- PRIMARY KEY (`order_log_id`)
);
