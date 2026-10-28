DROP TABLE IF EXISTS `post_field_value`;

CREATE TABLE `post_field_value` (
  `post_id` INT unsigned NOT NULL,
  `field_id` int(10) UNSIGNED NOT NULL,
  `language_id` int(10) UNSIGNED NOT NULL,
  `value` text NOT NULL,
  PRIMARY KEY  (`post_id`, `field_id`, `language_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE INDEX `post_field_value_field` ON `post_field_value` (`field_id`);
