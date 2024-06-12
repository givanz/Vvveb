DROP TABLE IF EXISTS `message`;

CREATE TABLE IF NOT EXISTS `message` (
  `message_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `type` varchar(20) NOT NULL DEFAULT 'message',
  `data` text NOT NULL,
  `meta` text NOT NULL,
  `status` tinyint(6) NOT NULL DEFAULT '0', -- unread = 0, read = 1
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`message_id`),
  KEY `type_status_date` (`status`, `type`,`created_at`,`message_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4;

