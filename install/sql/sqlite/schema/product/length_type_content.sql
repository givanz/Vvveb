DROP TABLE IF EXISTS `length_type_content`;

CREATE TABLE `length_type_content` (
`length_type_id` INT NOT NULL,
`language_id` INT NOT NULL,
`name` TEXT NOT NULL,
`unit` TEXT NOT NULL,
PRIMARY KEY (`length_type_id`,`language_id`)
);
