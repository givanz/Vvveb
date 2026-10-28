DROP TABLE IF EXISTS `manufacturer_to_site`;

CREATE TABLE `manufacturer_to_site` (
`manufacturer_id` INT NOT NULL,
`site_id` TINYINT NOT NULL DEFAULT 0,
PRIMARY KEY (`manufacturer_id`,`site_id`)
);

CREATE INDEX IF NOT EXISTS `manufacturer_to_site_site` ON `manufacturer_to_site` (`site_id`);
