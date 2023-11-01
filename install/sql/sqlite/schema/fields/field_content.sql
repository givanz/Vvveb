DROP TABLE IF EXISTS `field_content`;

CREATE TABLE `field_content` (
`field_id` int NOT NULL,
`language_id` int NOT NULL,
`name` TEXT NOT NULL
-- PRIMARY KEY (`field_id`,`language_id`)
);

CREATE INDEX `field_content_field_id_language_id` ON `field_content` (`field_id`,`language_id`);