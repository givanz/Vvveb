DROP TABLE IF EXISTS `media`;

CREATE TABLE `media` (
  `media_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `file` varchar(191) NOT NULL,
  `type` varchar(30) NOT NULL default 'image/png',
  `meta` TEXT,
  PRIMARY KEY (`media_id`),
  KEY `file_media_id` (`file`,`media_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4;
