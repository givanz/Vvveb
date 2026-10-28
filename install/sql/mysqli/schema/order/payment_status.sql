DROP TABLE IF EXISTS `payment_status`;

CREATE TABLE `payment_status` (
  `payment_status_id` INT UNSIGNED NOT NULL,
  `language_id` INT UNSIGNED NOT NULL,
  `name` varchar(32) NOT NULL,
  PRIMARY KEY (`payment_status_id`,`language_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
