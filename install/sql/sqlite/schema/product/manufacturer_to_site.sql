DROP TABLE IF EXISTS `manufacturer_to_site`;

CREATE TABLE `manufacturer_to_site` (
`manufacturer_id` INT NOT NULL,
`site_id` TINYINT NOT NULL,
PRIMARY KEY (`manufacturer_id`,`site_id`)
);
