DROP TABLE IF EXISTS `order_status`;

CREATE TABLE `order_status` (
  `order_status_id` INT UNSIGNED NOT NULL,
  `language_id` INT UNSIGNED NOT NULL,
  `name` varchar(32) NOT NULL,
  PRIMARY KEY (`order_status_id`,`language_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;