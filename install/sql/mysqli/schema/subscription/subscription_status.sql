DROP TABLE IF EXISTS `subscription_status`;

CREATE TABLE `subscription_status` (
  `subscription_status_id` INT UNSIGNED NOT NULL,
  `language_id` INT UNSIGNED NOT NULL,
  `name` varchar(32) NOT NULL,
  PRIMARY KEY (`subscription_status_id`,`language_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
