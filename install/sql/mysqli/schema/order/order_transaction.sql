DROP TABLE IF EXISTS `order_transaction`;

CREATE TABLE `order_transaction` (
  `order_transaction_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `order_id` INT UNSIGNED NOT NULL,
  `kind` varchar(32) NOT NULL DEFAULT '',
  `gateway` varchar(64) NOT NULL DEFAULT '',
  `reference` varchar(191) NOT NULL DEFAULT '',
  `amount` decimal(15,4) NOT NULL DEFAULT '0.0000',
  `tax_amount` decimal(15,4) NOT NULL DEFAULT '0.0000',
  `currency` varchar(3) NOT NULL DEFAULT '',
  `status` varchar(32) NOT NULL DEFAULT '',
  `note` text,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`order_transaction_id`),
  KEY `order_id` (`order_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4;
