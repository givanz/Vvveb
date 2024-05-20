DROP TABLE IF EXISTS `order_log`;

CREATE TABLE `order_log` (
  `order_log_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `order_id` INT UNSIGNED NOT NULL,
  `order_status_id` INT UNSIGNED NOT NULL,
  `notify` tinyint NOT NULL DEFAULT '0',
  `public` tinyint NOT NULL DEFAULT '0',
  `note` text NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`order_log_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4;
