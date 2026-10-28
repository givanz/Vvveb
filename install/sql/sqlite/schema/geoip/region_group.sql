DROP TABLE IF EXISTS `region_group`;

CREATE TABLE `region_group` (
`region_group_id` INTEGER PRIMARY KEY AUTOINCREMENT,
`name` TEXT NOT NULL,
`content` TEXT NOT NULL DEFAULT '',
`created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
-- PRIMARY KEY (`region_group_id`)
);
