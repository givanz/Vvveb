DROP TABLE IF EXISTS `digital_asset_content`;

CREATE TABLE `digital_asset_content` (
`digital_asset_id` INT NOT NULL,
`language_id` INT NOT NULL,
`name` TEXT NOT NULL,
PRIMARY KEY (`digital_asset_id`,`language_id`)
);
