DROP TABLE IF EXISTS `product_content_revision`;

CREATE TABLE `product_content_revision` (
  `product_id`  INT UNSIGNED NOT NULL,
  `language_id` INT UNSIGNED NOT NULL,
  `content` TEXT,
  `admin_id` INT UNSIGNED NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
--  PRIMARY KEY (`product_id`,`language_id`, `created_at`)
--  FULLTEXT `search` (`name`,`content`)
);

CREATE INDEX `product_content_revision_primary` ON `product_content_revision` (`product_id`,`language_id`, `created_at`,`admin_id`);