DROP TABLE IF EXISTS `coupon_product`;

CREATE TABLE `coupon_product` (
`coupon_product_id` INTEGER PRIMARY KEY AUTOINCREMENT,
`coupon_id` INT NOT NULL,
`product_id` INT NOT NULL
-- PRIMARY KEY (`coupon_product_id`)
);

CREATE INDEX `coupon_product_coupon_id` ON `coupon_product` (`coupon_id`);
CREATE INDEX `coupon_product_product_id` ON `coupon_product` (`product_id`);





