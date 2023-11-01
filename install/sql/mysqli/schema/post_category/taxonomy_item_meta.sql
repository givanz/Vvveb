DROP TABLE IF EXISTS `taxonomy_item_meta`;

CREATE TABLE `taxonomy_item_meta` (
  `meta_id` INT unsigned NOT NULL AUTO_INCREMENT,
  `taxonomy_item_id` INT unsigned NOT NULL DEFAULT '0',
  `key` varchar(191) DEFAULT NULL,
  `value` longtext,
  PRIMARY KEY (`meta_id`),
  KEY `taxonomy_item_id` (`taxonomy_item_id`),
  KEY `key` (`key`(191))
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4;
