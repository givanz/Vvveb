DROP TABLE IF EXISTS `product_content_revision`;

CREATE TABLE `product_content_revision` (
  `product_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `language_id` INT UNSIGNED NOT NULL,
  `content` longtext,
  `admin_id` INT UNSIGNED NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`product_id`,`language_id`, `created_at`, `admin_id`)
--  FULLTEXT `search` (`name`,`content`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
