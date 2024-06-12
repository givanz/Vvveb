DROP TABLE IF EXISTS `product_subscription`;

CREATE TABLE `product_subscription` (
`product_id` INT NOT NULL,
`subscription_plan_id` INT NOT NULL,
`user_group_id` INT NOT NULL,
`price` decimal(15,4) NOT NULL DEFAULT 0.0000,
`trial_price` decimal(15,4) NOT NULL DEFAULT 0.0000
-- PRIMARY KEY (`product_id`,`subscription_plan_id`,`user_group_id`)
);

CREATE INDEX `order_id` ON `product_subscription` (`product_id`,`subscription_plan_id`,`user_group_id`);