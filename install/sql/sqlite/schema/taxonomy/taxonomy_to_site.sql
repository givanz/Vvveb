DROP TABLE IF EXISTS `taxonomy_to_site`;

CREATE TABLE `taxonomy_to_site` (
`taxonomy_item_id` INT NOT NULL,
`site_id` TINYINT NOT NULL,
PRIMARY KEY (`taxonomy_item_id`,`site_id`)
);

CREATE INDEX IF NOT EXISTS `taxonomy_to_site_site` ON `taxonomy_to_site` (`site_id`);
