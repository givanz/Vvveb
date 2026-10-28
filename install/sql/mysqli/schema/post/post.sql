DROP TABLE IF EXISTS `post`;

CREATE TABLE `post` (
  `post_id` INT unsigned NOT NULL AUTO_INCREMENT COMMENT 'Unique identifier for the post',
  `admin_id` INT unsigned NOT NULL COMMENT 'The id for the author of the post',
  `status` varchar(20) NOT NULL DEFAULT 'publish' COMMENT 'The post status',
  `image` varchar(191) NOT NULL DEFAULT '' COMMENT '',
  `comment_status` varchar(20) NOT NULL DEFAULT 'open' COMMENT '',
  `password` varchar(191) NOT NULL DEFAULT '' COMMENT '',
  `parent` INT unsigned NOT NULL DEFAULT '0' COMMENT '',
  `sort_order` INT UNSIGNED NOT NULL DEFAULT '0' COMMENT '',
  `type` varchar(20) NOT NULL DEFAULT 'post' COMMENT '',
  `template` varchar(191) NOT NULL DEFAULT '' COMMENT '',
  `comment_count` INT NOT NULL DEFAULT '0' COMMENT '',
  `views` INT unsigned NOT NULL DEFAULT '0' COMMENT '',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'The date the post was created',
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'The date the post was last modified',
  PRIMARY KEY (`post_id`),
  KEY `type_status_date_post_id` (`type`,`status`,`sort_order`,`created_at`,`post_id`),
  KEY `parent` (`parent`),
  KEY `author` (`admin_id`),
  KEY `updated_at` (`updated_at`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4;
