DROP TABLE IF EXISTS `order_transaction`;

CREATE TABLE `order_transaction` (
`order_transaction_id` INTEGER PRIMARY KEY AUTOINCREMENT,
`order_id` INT NOT NULL,
`kind` TEXT NOT NULL DEFAULT '',
`gateway` TEXT NOT NULL DEFAULT '',
`reference` TEXT NOT NULL DEFAULT '',
`amount` decimal(15,4) NOT NULL DEFAULT '0.0000',
`tax_amount` decimal(15,4) NOT NULL DEFAULT '0.0000',
`currency` TEXT NOT NULL DEFAULT '',
`status` TEXT NOT NULL DEFAULT '',
`note` TEXT,
`created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
-- PRIMARY KEY (`order_transaction_id`)
);

CREATE INDEX `order_transaction_order_id` ON `order_transaction` (`order_id`);
