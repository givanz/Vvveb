DROP TABLE IF EXISTS `site`;

CREATE TABLE `site` (
  `site_id` tinyint(6) NOT NULL AUTO_INCREMENT,
  `key` varchar(191) NOT NULL,
  `name` varchar(191) NOT NULL,
  `host` varchar(191) NOT NULL,
  `theme` varchar(191) NOT NULL,
  `template` varchar(191) NOT NULL DEFAULT '',
  `settings` text,
  PRIMARY KEY (`site_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4;
