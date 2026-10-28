DROP TABLE IF EXISTS `subscription`;

CREATE TABLE `subscription` (
  `subscription_id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `order_id` INT NOT NULL,
  `order_product_id` INT NOT NULL,
  `site_id` INT NOT NULL,
  `user_id` INT NOT NULL,
  `payment_address_id` INT NOT NULL,
  `payment_method` text NOT NULL,
  `shipping_address_id` INT NOT NULL,
  `shipping_method` text NOT NULL,
  `product_id` INT NOT NULL,
  `quantity` int(4) NOT NULL,
  `subscription_plan_id` INT NOT NULL,
  `price` decimal(10,4) NOT NULL,
  `period` TEXT CHECK( period IN ('day','week','month','year') ) NOT NULL DEFAULT 'month',
  `cycle` smallint(6) NOT NULL,
  `length` smallint(6) NOT NULL,
  `left` smallint(6) NOT NULL,
  `trial_price` decimal(10,4) NOT NULL,
  `trial_period` TEXT CHECK( trial_period IN ('day','week','month','year') ) NOT NULL DEFAULT 'month',
  `trial_cycle` smallint(6) NOT NULL,
  `trial_length` smallint(6) NOT NULL,
  `trial_left` smallint(6) NOT NULL,
  `trial_status` tinyint NOT NULL,
  `date_next` datetime NOT NULL,
  `notes` text NOT NULL,
  `subscription_status_id` INT NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX `subscription_order_id` ON `subscription` (`order_id`);
CREATE INDEX `subscription_user_id` ON `subscription` (`user_id`);
CREATE INDEX `subscription_product_id` ON `subscription` (`product_id`);
CREATE INDEX `subscription_subscription_plan_id` ON `subscription` (`subscription_plan_id`);
CREATE INDEX `subscription_subscription_status_id` ON `subscription` (`subscription_status_id`);
