DROP TABLE IF EXISTS `order_shipment`;

CREATE TABLE `order_shipment` (
`order_shipment_id` INTEGER PRIMARY KEY AUTOINCREMENT,
`order_id` INT NOT NULL,
`shipping_method` TEXT NOT NULL,
`tracking_number` TEXT NOT NULL,
`created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
-- PRIMARY KEY (`order_shipment_id`)
);
