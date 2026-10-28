DROP TABLE IF EXISTS `setting_content`;

CREATE TABLE `setting_content` (
`site_id` INTEGER NOT NULL DEFAULT 0,
`language_id` INTEGER NOT NULL DEFAULT 0,
`namespace` TEXT NOT NULL,
`key` TEXT NOT NULL,
`value` text NOT NULL,
 PRIMARY KEY (`site_id`, `language_id`,`namespace`,`key`)
);
