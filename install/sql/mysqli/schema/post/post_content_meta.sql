DROP TABLE IF EXISTS `post_content_meta`;

CREATE TABLE `post_content_meta` (
  `post_id` INT unsigned NOT NULL,
  `language_id` INT unsigned NOT NULL,
  `namespace` varchar(32)  NOT NULL DEFAULT '',
  `key` varchar(191) NOT NULL,
  `value` longtext,
  PRIMARY KEY (`post_id`, `language_id`, `namespace`, `key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
