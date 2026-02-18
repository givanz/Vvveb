DROP TABLE IF EXISTS `taxonomy_item_field_value`;

CREATE TABLE `taxonomy_item_field_value` (
  `taxonomy_item_field_value_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `language_id` int(10) UNSIGNED NOT NULL,
  `taxonomy_item_id` INT unsigned NOT NULL,
  `field_id` int(10) UNSIGNED NOT NULL,
  `sort_order` int(3) NOT NULL DEFAULT 0,
  PRIMARY KEY (`taxonomy_item_field_value_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4;
