DROP TABLE IF EXISTS `product_subscription`;

CREATE TABLE `product_subscription` (
  `product_id` int(10) UNSIGNED NOT NULL,
  `subscription_plan_id` int(11) NOT NULL,
  `user_group_id` int(10) UNSIGNED NOT NULL,
  `type` char(1) NOT NULL DEFAULT '', -- empty = original price, p = percentage discount, f = fixed discount, n = flat price
  `discount` decimal(15,4) NOT NULL DEFAULT 0,
  `price` decimal(10,4) NOT NULL,
  `trial_price` decimal(10,4) NOT NULL,
  PRIMARY KEY (`product_id`,`subscription_plan_id`,`user_group_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
