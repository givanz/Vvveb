DROP TABLE IF EXISTS `tax_type`;

CREATE TABLE `tax_type` (
`tax_type_id` INTEGER PRIMARY KEY AUTOINCREMENT,
`name` TEXT NOT NULL,
`content` TEXT NOT NULL,
`created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
`updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
-- PRIMARY KEY (`tax_type_id`)
);
