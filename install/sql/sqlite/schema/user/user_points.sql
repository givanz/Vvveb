DROP TABLE IF EXISTS `user_points`;

CREATE TABLE `user_points` (
`user_points_id` INTEGER PRIMARY KEY AUTOINCREMENT,
`user_id` INT NOT NULL DEFAULT '0',
`order_id` INT NOT NULL DEFAULT '0',
`content` text NOT NULL,
`points` INTEGER NOT NULL DEFAULT '0',
`created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
-- PRIMARY KEY (`user_points_id`)
);
