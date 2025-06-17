DROP TABLE IF EXISTS `menu_item_meta`;

CREATE TABLE `menu_item_meta` (
  `menu_item_id` INT NOT NULL,
  `namespace` TEXT NOT NULL DEFAULT '',
  `key` TEXT NOT NULL,
  `value` TEXT
  -- PRIMARY KEY (`menu_item_id`, `namespace`, `key`)
);

CREATE UNIQUE INDEX `menu_item_meta_menu_item_id` ON `menu_item_meta` (`menu_item_id`, `namespace`, `key`);
