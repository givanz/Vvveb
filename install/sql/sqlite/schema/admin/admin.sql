DROP TABLE IF EXISTS `admin`;

CREATE TABLE `admin` (
`admin_id` INTEGER PRIMARY KEY AUTOINCREMENT,
`username` TEXT NOT NULL DEFAULT '',
`first_name` TEXT NOT NULL DEFAULT '',
`last_name` TEXT NOT NULL DEFAULT '',
`password` TEXT NOT NULL DEFAULT '',
`email` TEXT NOT NULL DEFAULT '',
`phone_number` TEXT NOT NULL DEFAULT '',
`url` TEXT NOT NULL DEFAULT '',
`display_name` TEXT NOT NULL DEFAULT '',
`avatar` TEXT NOT NULL DEFAULT '',
`bio` TEXT NOT NULL DEFAULT '',
`role_id` INT  DEFAULT NULL,
`site_access` INT NOT NULL DEFAULT '[]',
`status` INT NOT NULL DEFAULT '0',
`secret` TEXT NOT NULL DEFAULT '',
`token` TEXT NOT NULL DEFAULT '',
`created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
`updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX `admin_user` ON `admin` (`username`);
CREATE INDEX `admin_email` ON `admin` (`email`);
