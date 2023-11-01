DROP TABLE IF EXISTS `attribute`;

CREATE TABLE `attribute` (
  `attribute_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `attribute_group_id` int(10) UNSIGNED NOT NULL,
  `sort_order` int(3) NOT NULL,
  PRIMARY KEY (`attribute_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
