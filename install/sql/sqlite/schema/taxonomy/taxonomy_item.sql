DROP TABLE IF EXISTS `taxonomy_item`;

CREATE TABLE `taxonomy_item` (
`taxonomy_item_id` INTEGER PRIMARY KEY AUTOINCREMENT,
`taxonomy_id` INT NOT NULL,
`image` TEXT NOT NULL DEFAULT '',
`template` TEXT NOT NULL DEFAULT '',
`parent_id` INT NOT NULL DEFAULT '0',
`item_id` INT  DEFAULT NULL, -- post or product id
`sort_order` INTEGER NOT NULL DEFAULT 0,
`status` TINYINT NOT NULL DEFAULT 0
-- PRIMARY KEY (`taxonomy_item_id`)
);

CREATE INDEX `taxonomy_item_parent_id` ON `taxonomy_item` (`parent_id`);