DROP TABLE IF EXISTS `user_group`;

CREATE TABLE `user_group` (
`user_group_id` INTEGER PRIMARY KEY AUTOINCREMENT,
`status` INTEGER NOT NULL,
`sort_order` INTEGER NOT NULL DEFAULT 0
-- PRIMARY KEY (`user_group_id`)
);
