DROP TABLE IF EXISTS `voucher_log`;

CREATE TABLE `voucher_log` (
`voucher_log_id` INTEGER PRIMARY KEY AUTOINCREMENT,
`voucher_id` INT NOT NULL,
`order_id` INT NOT NULL,
`credit` decimal(15,4) NOT NULL,
`created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
-- PRIMARY KEY (`voucher_log_id`)
);

CREATE INDEX `voucher_log_voucher_id` ON `voucher_log` (`voucher_id`);
CREATE INDEX `voucher_log_order_id` ON `voucher_log` (`order_id`);
