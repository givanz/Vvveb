DROP TABLE IF EXISTS `taxonomy_item_meta`;

CREATE TABLE `taxonomy_item_meta` (
  `taxonomy_item_id` INT unsigned NOT NULL,
  `namespace` varchar(32) NOT NULL DEFAULT '',
  `key` varchar(191) NOT NULL ,
  `value` longtext,
   PRIMARY KEY (`taxonomy_item_id`,`namespace`,`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
