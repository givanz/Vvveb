DROP TABLE IF EXISTS `product_content_meta`;

CREATE TABLE `product_content_meta` (
  `product_id` INT unsigned NOT NULL,
  `language_id` INT unsigned NOT NULL,
  `namespace` varchar(32)  NOT NULL DEFAULT '',
  `key` varchar(191) NOT NULL,
  `value` longtext,
  PRIMARY KEY (`product_id`, `language_id`, `namespace`, `key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
