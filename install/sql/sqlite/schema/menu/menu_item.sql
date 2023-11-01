DROP TABLE IF EXISTS `menu_item`;

CREATE TABLE `menu_item` (
`menu_item_id` INTEGER PRIMARY KEY AUTOINCREMENT,
`menu_id` INT NOT NULL,
`image` TEXT NOT NULL DEFAULT '',
`url` TEXT NOT NULL DEFAULT '',
`parent_id` INT NOT NULL DEFAULT '0',
`item_id` INT  DEFAULT NULL, -- post or product id
`sort_order` INTEGER NOT NULL DEFAULT 0,
`status` TINYINT NOT NULL DEFAULT 0
-- PRIMARY KEY (`menu_item_id`)
);

CREATE INDEX `menu_item_parent_id` ON `menu_item` (`parent_id`);
