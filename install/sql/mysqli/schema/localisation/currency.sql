DROP TABLE IF EXISTS `currency`;

CREATE TABLE `currency` (
  `currency_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(32) NOT NULL,
  `code` varchar(3) NOT NULL,
  `value` double(15,8) NOT NULL DEFAULT 1,
  `sign_start` varchar(12) NOT NULL DEFAULT '',
  `sign_end` varchar(12) NOT NULL DEFAULT '',
  `decimal_place` tinyint(1) NOT NULL DEFAULT 2,
  `status` tinyint NOT NULL DEFAULT 0,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`currency_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4;