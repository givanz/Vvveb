DROP TABLE IF EXISTS `option_value`;

CREATE TABLE `option_value` (
  `option_value_id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `option_id` INT NOT NULL,
  `image` TEXT NOT NULL,
  `sort_order` INT NOT NULL
--  PRIMARY KEY (`option_value_id`)
);