DROP TABLE IF EXISTS `menu_item_meta`;

CREATE TABLE `menu_item_meta` (
  `menu_item_id` INT unsigned NOT NULL,
  `namespace` varchar(191) NOT NULL DEFAULT '',
  `key` varchar(191) NOT NULL,
  `value` longtext,
  PRIMARY KEY (`menu_item_id`,`namespace`,`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
