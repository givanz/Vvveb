DROP TABLE IF EXISTS `site`;

CREATE TABLE `site` (
  `site_id` tinyint(6) NOT NULL AUTO_INCREMENT,
  `name` varchar(191) NOT NULL,
  `settings` text,
  PRIMARY KEY (`site_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4;
