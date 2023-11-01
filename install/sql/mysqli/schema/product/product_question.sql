DROP TABLE IF EXISTS `product_question`;

CREATE TABLE `product_question` (
  `product_question_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `product_id` INT UNSIGNED NOT NULL,
  `user_id` INT UNSIGNED NOT NULL,
  `author` varchar(64) NOT NULL,
  `content` text NOT NULL,
  `status` tinyint UNSIGNED NOT NULL DEFAULT '0',
  `parent_id` INT UNSIGNED NOT NULL DEFAULT '0',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`product_question_id`),
  KEY `product_id` (`product_id`, `status`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4;
