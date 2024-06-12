DROP TABLE IF EXISTS `taxonomy_item`;

CREATE TABLE `taxonomy_item` (
  `taxonomy_item_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `taxonomy_id` INT UNSIGNED NOT NULL,
  `image` varchar(191) NOT NULL DEFAULT '',
  `template` varchar(191) NOT NULL DEFAULT '',
  `parent_id` INT UNSIGNED NOT NULL DEFAULT '0',
  `item_id` INT UNSIGNED DEFAULT NULL, -- post or product id
  `sort_order` int(3) NOT NULL DEFAULT 0,
  `status` tinyint NOT NULL DEFAULT 0,
  PRIMARY KEY (`taxonomy_item_id`),
  KEY `parent_id` (`parent_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4;

