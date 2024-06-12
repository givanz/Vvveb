DROP TABLE IF EXISTS `weight_type`;

CREATE TABLE `weight_type` (
  `weight_type_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `value` decimal(15,8) NOT NULL DEFAULT '0.00000000',
  PRIMARY KEY (`weight_type_id`)
) ENGINE=InnoDb AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4;
