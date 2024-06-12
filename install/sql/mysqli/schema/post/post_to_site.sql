DROP TABLE IF EXISTS `post_to_site`;

CREATE TABLE `post_to_site` (
  `post_id` INT UNSIGNED NOT NULL,
  `site_id` tinyint(6) NOT NULL DEFAULT '0',
  PRIMARY KEY (`post_id`,`site_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
