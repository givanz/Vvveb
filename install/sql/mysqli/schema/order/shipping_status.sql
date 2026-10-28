DROP TABLE IF EXISTS `shipping_status`;

CREATE TABLE `shipping_status` (
  `shipping_status_id` INT UNSIGNED NOT NULL,
  `language_id` INT UNSIGNED NOT NULL,
  `name` varchar(32) NOT NULL,
  PRIMARY KEY (`shipping_status_id`,`language_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
