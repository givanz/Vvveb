DROP TABLE IF EXISTS `subscription_plan_content`;

CREATE TABLE `subscription_plan_content` (
  `subscription_plan_id` int(10) UNSIGNED NOT NULL,
  `language_id` int(10) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  PRIMARY KEY (`subscription_plan_id`,`language_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4;
