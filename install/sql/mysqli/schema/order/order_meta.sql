DROP TABLE IF EXISTS `order_meta`;

CREATE TABLE `order_meta` (
  `meta_id` INT unsigned NOT NULL AUTO_INCREMENT,
  `order_id` INT unsigned NOT NULL DEFAULT '0',
  `key` varchar(191) DEFAULT NULL,
  `value` longtext,
  PRIMARY KEY (`meta_id`),
  KEY `order_id` (`order_id`),
  KEY `key` (`key`(191))
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4;
