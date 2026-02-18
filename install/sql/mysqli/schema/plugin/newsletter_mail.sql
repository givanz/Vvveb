-- DROP TABLE IF EXISTS `newsletter_mail`;

CREATE TABLE IF NOT EXISTS `newsletter_mail` (
  `newsletter_list_id` INT UNSIGNED,
  `email` varchar(254) NOT NULL,
  `first_name` varchar(254) NOT NULL DEFAULT '',
  `last_name` varchar(254) NOT NULL DEFAULT '',
  `data` text NOT NULL DEFAULT '',
  `status` tinyint(6) NOT NULL DEFAULT '0', -- inactive = 0, active = 1, unsubsubscribed = 2 
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`email`, `newsletter_list_id`),
  KEY `newsletter_list` (`newsletter_list_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4;
