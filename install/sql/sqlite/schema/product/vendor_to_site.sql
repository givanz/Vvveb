DROP TABLE IF EXISTS `vendor_to_site`;

CREATE TABLE `vendor_to_site` (
`vendor_id` INT NOT NULL,
`site_id` TINYINT NOT NULL,
PRIMARY KEY (`vendor_id`,`site_id`)
);
