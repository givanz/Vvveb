DROP TABLE IF EXISTS `product_review_media`;

CREATE TABLE `product_review_media` (
  `product_review_media_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `product_review_id` INT UNSIGNED NOT NULL,
  `product_id` INT UNSIGNED NOT NULL,
  `user_id` INT UNSIGNED NOT NULL,
  `image` varchar(191) NOT NULL,
  `sort_order` int(3) NOT NULL DEFAULT '0',
  PRIMARY KEY (`product_review_media_id`),
  KEY `product_review_id` (`product_review_id`),
  KEY `product_id` (`product_id`,`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4;
