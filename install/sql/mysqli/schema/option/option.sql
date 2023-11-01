DROP TABLE IF EXISTS `option`;

CREATE TABLE `option` (
  `option_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `type` varchar(64) NOT NULL,
   `sort_order` int(3) NOT NULL,
  PRIMARY KEY (`option_id`, `type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
