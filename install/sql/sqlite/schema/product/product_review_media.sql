DROP TABLE IF EXISTS `product_review_media`;

CREATE TABLE `product_review_media` (
`product_review_media_id` INTEGER PRIMARY KEY AUTOINCREMENT,
`product_review_id` INT NOT NULL,
`product_id` INT NOT NULL,
`user_id` INT NOT NULL,
`image` TEXT NOT NULL,
`sort_order` INTEGER NOT NULL DEFAULT '0'
-- PRIMARY KEY (`product_review_media_id`)
);


CREATE INDEX `product_review_media_product_id_image` ON `product_review_media` (`product_id`, `user_id`);
CREATE INDEX `product_review_media_product_review_id_image` ON `product_review_media` (`product_review_id`);
