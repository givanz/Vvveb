DROP TABLE IF EXISTS `coupon_log`;

CREATE TABLE `coupon_log` (
  `coupon_log_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `coupon_id` INT UNSIGNED NOT NULL,
  `order_id` INT UNSIGNED NOT NULL,
  `user_id` INT UNSIGNED NOT NULL,
  `discount` decimal(15,4) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`coupon_log_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4;
