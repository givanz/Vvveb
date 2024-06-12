DROP TABLE IF EXISTS `menu`;

CREATE TABLE `menu` (
  `menu_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(191) NOT NULL DEFAULT '',
  `slug` varchar(191) NOT NULL DEFAULT '',
  PRIMARY KEY (`menu_id`),
  KEY `menu_id` (`menu_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4;
