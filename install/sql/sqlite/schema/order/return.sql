DROP TABLE IF EXISTS `return`;

CREATE TABLE `return` (
`return_id` INTEGER PRIMARY KEY AUTOINCREMENT,
`order_id` INT NOT NULL,
`customer_order_id` TEXT NOT NULL DEFAULT 0,
`product_id` INT NOT NULL,
`user_id` INT NOT NULL,
`first_name` TEXT NOT NULL,
`last_name` TEXT NOT NULL,
`email` TEXT NOT NULL,
`phone_number` TEXT NOT NULL,
`product` TEXT NOT NULL,
`model` TEXT NOT NULL,
`quantity` INTEGER NOT NULL,
`opened` TINYINT NOT NULL,
`return_reason_id` INT NOT NULL,
`return_resolution_id` INT NOT NULL DEFAULT 0,
`return_status_id` INT NOT NULL DEFAULT 0,
`note` text NOT NULL,
`date_ordered` date NOT NULL,
`created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
`updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
-- PRIMARY KEY (`return_id`)
);

