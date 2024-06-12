DROP TABLE IF EXISTS `attribute`;

CREATE TABLE `attribute` (
  `attribute_id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `attribute_group_id` INT NOT NULL,
  `sort_order` INT NOT NULL DEFAULT 0
--  PRIMARY KEY (`attribute_id`)
);
