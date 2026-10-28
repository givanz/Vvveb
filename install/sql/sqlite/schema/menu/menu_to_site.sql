DROP TABLE IF EXISTS `menu_to_site`;

CREATE TABLE `menu_to_site` (
`menu_id` INT NOT NULL,
`site_id` TINYINT NOT NULL,
PRIMARY KEY (`menu_id`,`site_id`)
);

CREATE INDEX IF NOT EXISTS `menu_to_site_site` ON `menu_to_site` (`site_id`);
