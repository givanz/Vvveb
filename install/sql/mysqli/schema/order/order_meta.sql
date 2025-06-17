DROP TABLE IF EXISTS `order_meta`;

CREATE TABLE `order_meta` (
  `order_id` INT unsigned NOT NULL,
  `namespace` varchar(32) NOT NULL DEFAULT '',
  `key` varchar(191) NOT NULL,
  `value` longtext,
   PRIMARY KEY (`order_id`,`namespace`,`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
