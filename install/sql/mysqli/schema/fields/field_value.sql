DROP TABLE IF EXISTS `field_value`;

CREATE TABLE `field_value` (
  `field_value_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `field_id` int(10) UNSIGNED NOT NULL,
  `sort_order` int(3) NOT NULL,
  PRIMARY KEY (`field_value_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4;
