DROP TABLE IF EXISTS `option`;

CREATE TABLE `option` (
  `option_id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `type` TEXT NOT NULL,
  `sort_order` INT NOT NULL
--  PRIMARY KEY (`option_id`, `type`)
);
