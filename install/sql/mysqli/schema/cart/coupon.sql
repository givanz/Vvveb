DROP TABLE IF EXISTS `coupon`;

CREATE TABLE `coupon` (
  `coupon_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(128) NOT NULL,
  `code` varchar(20) NOT NULL,
  `type` char(1) NOT NULL,
  `discount` decimal(15,4) NOT NULL,
  `total` decimal(15,4) NOT NULL,
  `limit` INT UNSIGNED NOT NULL,
  `limit_user` varchar(11) NOT NULL,
  `logged_in` tinyint NOT NULL,
  `free_shipping` tinyint NOT NULL,
  `status` tinyint NOT NULL,
  `from_date` date,
  `to_date` date,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`coupon_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4;
