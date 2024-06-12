DROP TABLE IF EXISTS `length_type`;

CREATE TABLE `length_type` (
  `length_type_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `value` decimal(15,8) NOT NULL,
  PRIMARY KEY (`length_type_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4;