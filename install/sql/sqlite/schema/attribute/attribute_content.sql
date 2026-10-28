DROP TABLE IF EXISTS `attribute_content`;

CREATE TABLE `attribute_content` (
  `attribute_id` INTEGER NOT NULL,
  `language_id` INT NOT NULL,
  `name` TEXT NOT NULL,
 PRIMARY KEY (`attribute_id`,`language_id`)
);
