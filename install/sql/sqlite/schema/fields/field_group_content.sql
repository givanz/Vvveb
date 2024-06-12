DROP TABLE IF EXISTS `field_group_content`;

CREATE TABLE `field_group_content` (
`field_group_id` INT NOT NULL,
`language_id` INT NOT NULL,
`name` TEXT NOT NULL
--PRIMARY KEY (`field_group_id`,`language_id`)
);

CREATE INDEX `field_group_content_field_id_language_id` ON `field_group_content` (`field_group_id`,`language_id`);
