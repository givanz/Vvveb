DROP TABLE IF EXISTS `field`;

CREATE TABLE `field` (
  `field_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `field_group_id` int NOT NULL,
  `type` varchar(32) NOT NULL,
  `value` text NOT NULL,
  `status` tinyint NOT NULL DEFAULT 0,
  `sort_order` int NOT NULL DEFAULT 0,
  PRIMARY KEY (`field_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4;
