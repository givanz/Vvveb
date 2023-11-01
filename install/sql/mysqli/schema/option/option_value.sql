DROP TABLE IF EXISTS `option_value`;

CREATE TABLE `option_value` (
  `option_value_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `option_id` int(10) UNSIGNED NOT NULL,
  `image` varchar(255) NOT NULL,
  `sort_order` int(3) NOT NULL,
  PRIMARY KEY (`option_value_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
