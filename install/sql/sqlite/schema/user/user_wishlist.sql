DROP TABLE IF EXISTS `user_wishlist`;

CREATE TABLE `user_wishlist` (
`user_id` INT NOT NULL,
`product_id` INT NOT NULL,
`created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
PRIMARY KEY (`user_id`,`product_id`)
);