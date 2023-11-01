DROP TABLE IF EXISTS `option_content`;

CREATE TABLE `option_content` (
  `option_id` INT NOT NULL,
  `language_id` INT NOT NULL,
  `name` TEXT NOT NULL,
  PRIMARY KEY (`option_id`,`language_id`)
);