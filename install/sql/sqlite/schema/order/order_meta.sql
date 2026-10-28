DROP TABLE IF EXISTS `order_meta`;

CREATE TABLE `order_meta` (
  `order_id` INT NOT NULL,
  `namespace` TEXT NOT NULL DEFAULT '',
  `key` TEXT NOT NULL,
  `value` TEXT,
  PRIMARY KEY (`order_id`,`namespace`,`key`)
);
