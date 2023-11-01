DROP TABLE IF EXISTS `voucher_log`;

CREATE TABLE `voucher_log` (
  `voucher_log_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `voucher_id` INT UNSIGNED NOT NULL,
  `order_id` INT UNSIGNED NOT NULL,
  `credit` decimal(15,4) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`voucher_log_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4;
