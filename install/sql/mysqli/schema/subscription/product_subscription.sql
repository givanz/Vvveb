DROP TABLE IF EXISTS `product_subscription`;

CREATE TABLE `product_subscription` (
  `product_id` int(10) UNSIGNED NOT NULL,
  `subscription_plan_id` int(11) NOT NULL,
  `user_group_id` int(10) UNSIGNED NOT NULL,
  `price` decimal(10,4) NOT NULL,
  `trial_price` decimal(10,4) NOT NULL,
--  PRIMARY KEY (`product_id`,`subscription_plan_id`,`user_group_id`)
  KEY (`product_id`,`subscription_plan_id`,`user_group_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4;
