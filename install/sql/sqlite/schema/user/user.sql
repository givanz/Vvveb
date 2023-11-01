DROP TABLE IF EXISTS `user`;

CREATE TABLE `user` (
`user_id` INTEGER PRIMARY KEY AUTOINCREMENT,
`user_group_id` INT NOT NULL DEFAULT 1,
`username` TEXT NOT NULL DEFAULT '',
`first_name` TEXT NOT NULL DEFAULT '',
`last_name` TEXT NOT NULL DEFAULT '',
`password` TEXT NOT NULL DEFAULT '',
`email` TEXT NOT NULL DEFAULT '',
`phone_number` TEXT NOT NULL DEFAULT '',
`url` TEXT NOT NULL DEFAULT '',
`status` INT NOT NULL DEFAULT 0,
`display_name` TEXT NOT NULL DEFAULT '',
`token` TEXT NOT NULL DEFAULT '',
`subscribe` INT NOT NULL DEFAULT 0,
`created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
`updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
-- PRIMARY KEY (`user_id`)
);

CREATE INDEX `user_username` ON `user` (`username`);
CREATE INDEX `user_email` ON `user` (`email`);
