DROP TABLE IF EXISTS `product_meta`;

CREATE TABLE `product_meta` (
  `product_id` INT unsigned NOT NULL,
  `namespace` varchar(32) NOT NULL,
  `key` varchar(191) NOT NULL DEFAULT '',
  `value` longtext,
   PRIMARY KEY (`product_id`,`namespace`,`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
