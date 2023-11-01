DROP TABLE IF EXISTS `coupon_taxonomy`;

CREATE TABLE `coupon_taxonomy` (
`coupon_id` INT NOT NULL,
`taxonomy_item_id` INT NOT NULL,
PRIMARY KEY (`coupon_id`,`taxonomy_item_id`)
);
