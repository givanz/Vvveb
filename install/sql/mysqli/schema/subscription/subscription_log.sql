DROP TABLE IF EXISTS `subscription_log`;

CREATE TABLE `subscription_log` (
  `subscription_log_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `subscription_id` int(10) UNSIGNED NOT NULL,
  `subscription_status_id` int(10) UNSIGNED NOT NULL,
  `notify` tinyint NOT NULL DEFAULT 0,
  `note` text NOT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`subscription_log_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4;
