DROP TABLE IF EXISTS `field`;

CREATE TABLE `field` (
  `field_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `field_group_id` int NOT NULL,
  `type` varchar(32) NOT NULL,
  `default` varchar(192) NOT NULL DEFAULT '',
  `settings` text NOT NULL,
  `validation` text NOT NULL,
  `presentation` text NOT NULL,
  `conditionals` text NOT NULL,
  `row` int NOT NULL DEFAULT 0,
  `status` tinyint NOT NULL DEFAULT 1,
  `sort_order` int NOT NULL DEFAULT 0,
  PRIMARY KEY (`field_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4;
