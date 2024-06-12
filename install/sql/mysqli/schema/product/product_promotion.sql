DROP TABLE IF EXISTS `product_promotion`;

CREATE TABLE `product_promotion` (
  `product_promotion_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `product_id` INT UNSIGNED NOT NULL,
  `user_group_id` INT UNSIGNED NOT NULL,
  `priority` int NOT NULL DEFAULT '1',
  `price` decimal(15,4) NOT NULL DEFAULT '0.0000',
  `from_date` date,
  `to_date` date,
  PRIMARY KEY (`product_promotion_id`),

  KEY `product_id` (`product_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4;
