DROP TABLE IF EXISTS `setting_content`;

CREATE TABLE `setting_content` (
  `site_id` tinyint(6) UNSIGNED NOT NULL DEFAULT 0,
  `language_id` INT unsigned NOT NULL DEFAULT '0',
  `namespace` varchar(128) NOT NULL,
  `key` varchar(128) NOT NULL,
  `value` text NOT NULL,
  PRIMARY KEY (`site_id`, `language_id`, `namespace`, `key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
