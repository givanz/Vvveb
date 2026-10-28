DROP TABLE IF EXISTS `coupon_log`;

CREATE TABLE `coupon_log` (
`coupon_log_id` INTEGER PRIMARY KEY AUTOINCREMENT,
`coupon_id` INT NOT NULL,
`order_id` INT NOT NULL,
`user_id` INT NOT NULL,
`discount` decimal(15,4) NOT NULL,
`created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
--PRIMARY KEY (`coupon_log_id`)
);

CREATE INDEX `coupon_log_coupon_id` ON `coupon_log` (`coupon_id`);
CREATE INDEX `coupon_log_order_id` ON `coupon_log` (`order_id`);
CREATE INDEX `coupon_log_user_id` ON `coupon_log` (`user_id`);
