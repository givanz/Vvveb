DROP TABLE IF EXISTS `attribute_group`;

CREATE TABLE `attribute_group` (
  `attribute_group_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `sort_order` int(3) NOT NULL,
  PRIMARY KEY (`attribute_group_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
