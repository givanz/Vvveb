DROP TABLE IF EXISTS `subscription_log`;

CREATE TABLE `subscription_log` (
  `subscription_log_id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `subscription_id` INT NOT NULL,
  `subscription_status_id` INT NOT NULL,
  `notify` tinyint NOT NULL DEFAULT 0,
  `note` text NOT NULL,
  `created_at` datetime NOT NULL
--  PRIMARY KEY (`subscription_log_id`)
);
