DROP TABLE IF EXISTS `product`;

CREATE TABLE `product` (
`product_id` INTEGER PRIMARY KEY AUTOINCREMENT,
`admin_id` INT NOT NULL,
`model` TEXT NOT NULL DEFAULT '',
`sku` TEXT NOT NULL DEFAULT '',
`upc` TEXT NOT NULL DEFAULT '',
`ean` TEXT NOT NULL DEFAULT '',
`jan` TEXT NOT NULL DEFAULT '',
`isbn` TEXT NOT NULL DEFAULT '',
`mpn` TEXT NOT NULL DEFAULT '',
`barcode` TEXT NOT NULL DEFAULT '',
`hs_code` TEXT NOT NULL DEFAULT '',
`origin_country` TEXT NOT NULL DEFAULT '',
`location` TEXT NOT NULL DEFAULT '',
`mid_code` TEXT NOT NULL DEFAULT '',
`stock_quantity` INTEGER NOT NULL DEFAULT 1,
`stock_status_id` INT NOT NULL DEFAULT 1,
`image` TEXT NOT NULL DEFAULT '',
`manufacturer_id` INT NOT NULL DEFAULT 0,
`vendor_id` INT NOT NULL DEFAULT 0,
`requires_shipping` TINYINT NOT NULL DEFAULT 1,
`price` decimal(15,4) NOT NULL DEFAULT 0.0000,
`old_price` decimal(15,4) NOT NULL DEFAULT 0.0000,
`points` INTEGER NOT NULL DEFAULT 0,
`tax_type_id` INT NOT NULL DEFAULT 0,
`material` TEXT NOT NULL DEFAULT '',
`weight` decimal(15,8) NOT NULL DEFAULT '0.00000000',
`weight_type_id` INT NOT NULL DEFAULT '0',
`length` decimal(15,8) NOT NULL DEFAULT '0.00000000',
`width` decimal(15,8) NOT NULL DEFAULT '0.00000000',
`height` decimal(15,8) NOT NULL DEFAULT '0.00000000',
`length_type_id` INT NOT NULL DEFAULT 0,
`date_available` date NOT NULL DEFAULT '1000-01-01',
`template` TEXT NOT NULL DEFAULT '',
`views` INTEGER NOT NULL DEFAULT 0,
`subtract_stock` TINYINT NOT NULL DEFAULT 1,
`minimum_quantity` INT NOT NULL DEFAULT 1,
`subscription_onetime` TINYINT NOT NULL DEFAULT 1,
`type` TEXT NOT NULL DEFAULT 'product',
`status` TINYINT NOT NULL DEFAULT 0,
`sort_order` INT NOT NULL DEFAULT 0,
`created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
`updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
-- PRIMARY KEY (`product_id`)
);



CREATE INDEX `product_type_status_date` ON `product` (`type`);

