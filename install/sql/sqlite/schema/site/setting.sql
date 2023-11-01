DROP TABLE IF EXISTS `setting`;

CREATE TABLE `setting` (
`site_id` INTEGER,
`namespace` TEXT NOT NULL,
`key` TEXT NOT NULL,
`value` text NOT NULL,
 PRIMARY KEY (`site_id`, `namespace`, `key`)
);
