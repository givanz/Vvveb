DROP TABLE IF EXISTS `user_field_value`;

CREATE TABLE `user_field_value` (
  `user_field_value_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `language_id` int(10) UNSIGNED NOT NULL,
  `user_id` INT unsigned NOT NULL,
  `field_id` int(10) UNSIGNED NOT NULL,
  `sort_order` int(3) NOT NULL DEFAULT 0,
  PRIMARY KEY (`user_field_value_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4;
