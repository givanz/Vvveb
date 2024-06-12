DROP TABLE IF EXISTS `weight_type_content`;

CREATE TABLE `weight_type_content` (
`weight_type_id` INT NOT NULL,
`language_id` INT NOT NULL,
`name` TEXT NOT NULL,
`unit` TEXT NOT NULL,
PRIMARY KEY (`weight_type_id`,`language_id`)
);
