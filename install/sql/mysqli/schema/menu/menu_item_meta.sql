DROP TABLE IF EXISTS `menu_item_meta`;

CREATE TABLE `menu_item_meta` (
  `menu_item_meta_id` INT unsigned NOT NULL AUTO_INCREMENT,
  `menu_item_id` INT unsigned NOT NULL DEFAULT '0',
  `key` varchar(191) DEFAULT NULL,
  `value` longtext,
  PRIMARY KEY (`menu_item_meta_id`),
  KEY `menu_item_id` (`menu_item_id`),
  KEY `key` (`key`(191))
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4;
