DROP TABLE IF EXISTS `taxonomy_item_meta`;

CREATE TABLE `taxonomy_item_meta` (
  `taxonomy_item_id` INT NOT NULL,
  `namespace` TEXT NOT NULL DEFAULT '',
  `key` TEXT NOT NULL,
  `value` TEXT
  -- PRIMARY KEY (`taxonomy_item_id`, `namespace`, `key`)
);

CREATE UNIQUE INDEX `taxonomy_item_meta_taxonomy_item_id` ON `taxonomy_item_meta` (`taxonomy_item_id`, `namespace`, `key`);
