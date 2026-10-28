DROP TABLE IF EXISTS `setting`;

CREATE TABLE `setting` (
`site_id` INTEGER NOT NULL DEFAULT 0,
`namespace` TEXT NOT NULL,
`key` TEXT NOT NULL,
`value` text NOT NULL,
 PRIMARY KEY (`site_id`, `namespace`, `key`)
);
