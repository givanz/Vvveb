DROP TABLE IF EXISTS `comment`;

CREATE TABLE `comment` (
  `comment_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `post_id` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `user_id` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `author` tinytext NOT NULL,
  `email` varchar(100)  NOT NULL DEFAULT '',
  `url` varchar(200)  NOT NULL DEFAULT '',
  `ip` varchar(100)  NOT NULL DEFAULT '',
  `content` text  NOT NULL,
  `status` tinyint UNSIGNED NOT NULL DEFAULT 0,
  `votes` SMALLINT(3) UNSIGNED NOT NULL DEFAULT 0,
  `type` varchar(20)  NOT NULL DEFAULT '',
  `parent_id` INT UNSIGNED NOT NULL DEFAULT '0',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`comment_id`),
  KEY `post_id` (`post_id`, `status`),
  KEY `parent` (`parent_id`),
  KEY `email` (`email`(10))
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4;
