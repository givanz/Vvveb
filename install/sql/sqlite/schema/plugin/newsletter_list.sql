-- DROP TABLE IF EXISTS `newsletter_list`;

CREATE TABLE IF NOT EXISTS `newsletter_list` (
  `newsletter_list_id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `name` varchar(254) NOT NULL,
  `data` text NOT NULL DEFAULT '',
  `status` tinyint(6) NOT NULL DEFAULT '0', -- unread = 0, read = 1
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
--  PRIMARY KEY (`newsletter_list_id`)
);

