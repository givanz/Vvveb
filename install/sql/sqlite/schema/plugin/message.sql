DROP TABLE IF EXISTS `message`;

CREATE TABLE IF NOT EXISTS `message` (
`message_id` INTEGER PRIMARY KEY AUTOINCREMENT,
`type` TEXT NOT NULL DEFAULT 'publish',
`data` TEXT NOT NULL DEFAULT '',
`meta` TEXT NOT NULL DEFAULT '',
`status` TINYINT NOT NULL DEFAULT '0', -- unread = 0, read = 1
`created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
`updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
-- KEY `type_status_date` (`status`, `type`,`created_at`,`message_id`)
);

CREATE INDEX `message_status_type_date` ON `message` (`status`, `type`,`created_at`,`message_id`);
