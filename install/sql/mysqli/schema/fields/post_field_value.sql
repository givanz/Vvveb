DROP TABLE IF EXISTS `post_field_value`;

CREATE TABLE `post_field_value` (
  `post_id` INT unsigned NOT NULL,
  `field_id` int(10) UNSIGNED NOT NULL,
  `language_id` int(10) UNSIGNED NOT NULL,
  `value` text NOT NULL,
  PRIMARY KEY  (`post_id`, `field_id`, `language_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4;
