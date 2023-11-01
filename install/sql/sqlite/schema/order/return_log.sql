DROP TABLE IF EXISTS `return_log`;

CREATE TABLE `return_log` (
`return_log_id` INTEGER PRIMARY KEY AUTOINCREMENT,
`return_id` INT NOT NULL,
`return_status_id` INT NOT NULL,
`notify` TINYINT NOT NULL,
`note` text NOT NULL,
`created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
-- PRIMARY KEY (`return_log_id`)
);
