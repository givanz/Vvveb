DROP TABLE IF EXISTS `coupon_taxonomy`;

CREATE TABLE `coupon_taxonomy` (
  `coupon_id` INT UNSIGNED NOT NULL,
  `taxonomy_item_id` INT UNSIGNED NOT NULL,
  PRIMARY KEY (`coupon_id`,`taxonomy_item_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4;
