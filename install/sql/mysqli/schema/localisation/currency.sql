DROP TABLE IF EXISTS `currency`;

CREATE TABLE `currency` (
  `currency_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(32) NOT NULL,
  `code` varchar(3) NOT NULL,
  `value` double(15,8) NOT NULL,
  `sign_start` varchar(12) NOT NULL,
  `sign_end` varchar(12) NOT NULL,
  `decimal_place` char(1) NOT NULL,
  `status` tinyint NOT NULL,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`currency_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4;