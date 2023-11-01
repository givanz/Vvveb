DROP TABLE IF EXISTS `order_voucher`;

CREATE TABLE `order_voucher` (
`order_voucher_id` INTEGER PRIMARY KEY AUTOINCREMENT,
`order_id` INT NOT NULL,
`voucher_id` INT NOT NULL,
`content` TEXT NOT NULL,
`voucher` TEXT NOT NULL,
`from_name` TEXT NOT NULL,
`from_email` TEXT NOT NULL,
`to_name` TEXT NOT NULL,
`to_email` TEXT NOT NULL,
`message` text NOT NULL,
`amount` decimal(15,4) NOT NULL
-- PRIMARY KEY (`order_voucher_id`)
);
