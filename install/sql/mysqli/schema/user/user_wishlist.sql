DROP TABLE IF EXISTS `user_wishlist`;

CREATE TABLE `user_wishlist` (
  `user_id` INT UNSIGNED NOT NULL,
  `product_id` INT UNSIGNED NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`user_id`,`product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE INDEX `user_wishlist_product` ON `user_wishlist` (`product_id`);
