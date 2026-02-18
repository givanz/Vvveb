-- DROP TABLE IF EXISTS `newsletter_post`;

CREATE TABLE IF NOT EXISTS `newsletter_post` (
  `newsletter_post_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(191) NOT NULL,
  `content` longtext NOT NULL,
  `status` tinyint(6) NOT NULL DEFAULT 1, -- archived = 0, active = 1
  `sent` INT(6) NOT NULL DEFAULT 0,
  `open` INT(6) NOT NULL DEFAULT 0,
  `click` INT(6) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`newsletter_post_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4;

