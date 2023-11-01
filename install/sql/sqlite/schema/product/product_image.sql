DROP TABLE IF EXISTS `product_image`;

CREATE TABLE `product_image` (
`product_image_id` INTEGER PRIMARY KEY AUTOINCREMENT,
`product_id` INT NOT NULL,
`image` TEXT NOT NULL,
`sort_order` INTEGER NOT NULL DEFAULT '0'
-- PRIMARY KEY (`product_image_id`)
);



CREATE UNIQUE INDEX `product_image_product_id_image` ON `product_image` (`product_id`, `image`);
