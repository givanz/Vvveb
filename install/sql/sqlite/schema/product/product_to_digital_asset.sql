DROP TABLE IF EXISTS `product_to_digital_asset`;

CREATE TABLE `product_to_digital_asset` (
`product_id` INT NOT NULL,
`digital_asset_id` INT NOT NULL,
PRIMARY KEY (`product_id`,`digital_asset_id`)
);
