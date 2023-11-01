DROP TABLE IF EXISTS `subscription_plan`;

CREATE TABLE `subscription_plan` (
  `subscription_plan_id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `period` TEXT CHECK( period IN ('day','week','month','year') ) NOT NULL DEFAULT 'month',
  `length` INT NOT NULL,
  `cycle` INT NOT NULL,
  `trial_period` TEXT CHECK( trial_period IN ('day','week','month','year') ) NOT NULL DEFAULT 'month',
  `trial_length` INT NOT NULL,
  `trial_cycle` INT NOT NULL,
  `trial_status` tinyint(4) NOT NULL,
  `status` tinyint NOT NULL,
  `sort_order` int(3) NOT NULL
--  PRIMARY KEY (`subscription_plan_id`)
);
